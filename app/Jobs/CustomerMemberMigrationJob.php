<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\KycLog;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CustomerMemberMigrationJob implements ShouldQueue
{
    public $tries = 1;
    public $timeout = 2000;
    public $backoff = 4000;
    private $totalDuplicate = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('customer member migration job started');

        HealthMemberDetail::chunk(500, function ($healthMemberDetails) {
            foreach ($healthMemberDetails as $hqrmd) {
                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $hqrmd->health_quote_request_id)
                    ->where('quote_type', HealthQuote::class)
                    ->where('customer_entity_id', $hqrmd->customer_id)
                    ->where('code', $hqrmd->code)
                    ->exists();

                info('hqrmd id: '.$hqrmd->id);
                info('isDuplicate: '.$isDuplicate);

                if (! $isDuplicate) {
                    DB::table('customer_members')->insert([
                        'quote_type' => HealthQuote::class,
                        'quote_id' => $hqrmd->health_quote_request_id ?? null,
                        'customer_entity_id' => $hqrmd->customer_id ?? null,
                        'customer_type' => CustomerTypeEnum::Individual,
                        'code' => $hqrmd->code ?? null,
                        'first_name' => $hqrmd->first_name ?? null,
                        'last_name' => $hqrmd->last_name ?? null,
                        'gender' => $hqrmd->gender ?? null,
                        'dob' => $hqrmd->dob ?? null,
                        'nationality_id' => $hqrmd->nationality_id ?? null,
                        'member_category_id' => $hqrmd->member_category_id ?? null,
                        'salary_band_id' => $hqrmd->salary_band_id ?? null,
                        'emirate_of_your_visa_id' => $hqrmd->emirate_of_your_visa_id ?? null,
                        'relation_code' => $hqrmd->relation_code ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $this->totalDuplicate++;
                }
            }
        });

        TravelMemberDetail::chunk(500, function ($travelMemberDetails) {
            foreach ($travelMemberDetails as $tqrmd) {
                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $tqrmd->travel_quote_request_id)
                    ->where('quote_type', TravelQuote::class)
                    ->where('customer_entity_id', $tqrmd->customer_id)
                    ->where('code', $tqrmd->code)
                    ->exists();

                info('tqrmd id: '.$tqrmd->id);
                info('isDuplicate: '.$isDuplicate);

                if (! $isDuplicate) {
                    DB::table('customer_members')->insert([
                        'quote_type' => TravelQuote::class,
                        'quote_id' => $tqrmd->travel_quote_request_id ?? null,
                        'customer_entity_id' => $tqrmd->customer_id ?? null,
                        'customer_type' => CustomerTypeEnum::Individual,
                        'code' => $tqrmd->code ?? null,
                        'first_name' => $tqrmd->first_name ?? null,
                        'last_name' => $tqrmd->last_name ?? null,
                        'gender' => $tqrmd->gender ?? null,
                        'dob' => $tqrmd->dob ?? null,
                        'nationality_id' => $tqrmd->nationality_id ?? null,
                        'member_category_id' => $tqrmd->member_category_id ?? null,
                        'salary_band_id' => $tqrmd->salary_band_id ?? null,
                        'emirate_of_your_visa_id' => $tqrmd->emirate_of_your_visa_id ?? null,
                        'relation_code' => $tqrmd->relation_code ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'old_primary_member_id' => $tqrmd->id,
                    ]);
                } else {
                    $this->totalDuplicate++;
                }
            }
        });

        TravelQuote::whereNotNull('primary_member_id')->chunkById(500, function ($travelQuotes) {
            foreach ($travelQuotes as $travelQuote) {
                $customerMemberKey = CustomerMembers::whereHasMorph('quote', '\\App\\Models\\TravelQuote')
                    ->where(['quote_id' => $travelQuote->id, 'old_primary_member_id' => $travelQuote->primary_member_id])->first();

                info('tqr old primary_member_id: '.$travelQuote->primary_member_id.'tqr new primary_member_id:'.$customerMemberKey['id']);
                $travelQuote->update([
                    'primary_member_id' => $customerMemberKey['id'],
                ]);
            }
        });

        KycLog::withTrashed()->chunkById(100, function ($kycLogs) {
            foreach ($kycLogs as $kycLog) {
                if ($kycLog->match_found != null && $kycLog->match_found == 0) {
                    $kycLog->update([
                        'decision' => 'RYU',
                    ]);
                }
            }
        });

        info('total duplicates: '.$this->totalDuplicate);

        info('customer member migration job completed');
    }

    public function middleware()
    {
        return [(new WithoutOverlapping('CustomerMemberMigrationJob'))->dontRelease()];
    }
}
