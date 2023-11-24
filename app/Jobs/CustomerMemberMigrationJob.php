<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\KycLog;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CustomerMemberMigrationJob implements ShouldQueue
{
    public $tries = 2;
    public $timeout = 7200;
    public $backoff = 200;
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

        /*HealthMemberDetail::where('is_migrated', false)->orderBy('id', 'desc')->chunk(1000, function ($healthMemberDetails) {

            foreach ($healthMemberDetails as $hqrmd) {

                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $hqrmd->health_quote_request_id)
                    ->where('quote_type', HealthQuote::class)
                    ->where('customer_entity_id', $hqrmd->customer_id)
                    ->where('code', $hqrmd->code)
                    ->exists();

                if (! $isDuplicate) {
                    info('hqrmd id: '.$hqrmd->id);
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
                        'old_primary_member_id' => $hqrmd->id,
                    ]);



                } else {
                    info('isDuplicate: '.$isDuplicate);
                    $this->totalDuplicate++;
                }

                $hqrmd->update([
                    'is_migrated' => true,
                ]);
            }
        });*/

        HealthQuote::whereNotNull('primary_member_id')->orderBy('id', 'desc')->chunkById(1000, function ($healthQuotes) {
            foreach ($healthQuotes as $healthQuote) {
                $customerMemberKey = CustomerMembers::
                    where(['quote_type' => HealthQuote::class, 'quote_id' => $healthQuote->id, 'old_primary_member_id' => $healthQuote->primary_member_id])->first();

                if ($customerMemberKey) {
                    info('hqr quoteId: ' . $healthQuote->id . ' old primary_member_id: '.$healthQuote->primary_member_id.' - hqr new primary_member_id:'.$customerMemberKey->id);
                    $healthQuote->update([
                        'primary_member_id' => $customerMemberKey->id,
                    ]);
                }
            }
        });



        //        KycLog::withTrashed()->chunkById(100, function ($kycLogs) {
        //            foreach ($kycLogs as $kycLog) {
        //                if ($kycLog->match_found != null && $kycLog->match_found == 0) {
        //                    $kycLog->update([
        //                        'decision' => 'RYU',
        //                    ]);
        //                }
        //            }
        //        });

        info('total duplicates: '.$this->totalDuplicate);

        info('customer member migration job completed');
    }

    public function middleware()
    {
        return [(new WithoutOverlapping('CustomerMemberMigrationJob'))->dontRelease()];
    }
}
