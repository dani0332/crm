<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CustomerMemberTravelMigrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 7200;
    public $backoff = 200;

    private $totalDuplicate = 0;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public function retryUntil()
    {
        return Carbon::now()->addHours(2);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        TravelMemberDetail::where('is_migrated', false)->orderBy('id', 'desc')->chunk(1000, function ($travelMemberDetails) {

            foreach ($travelMemberDetails as $tqrmd) {

                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $tqrmd->travel_quote_request_id)
                    ->where('quote_type', TravelQuote::class)
                    ->where('customer_entity_id', $tqrmd->customer_id)
                    ->where('code', $tqrmd->code)
                    ->exists();

                if (! $isDuplicate) {
                    info('tqrmd id: '.$tqrmd->id);
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
                    info('isDuplicate: '.$isDuplicate);
                    $this->totalDuplicate++;
                }

                $tqrmd->update(['is_migrated' => true]);

            }
        });

//        TravelQuote::whereNotNull('primary_member_id')->chunkById(1000, function ($travelQuotes) {
//            foreach ($travelQuotes as $travelQuote) {
//                $customerMemberKey = CustomerMembers::whereHasMorph('quote', '\\App\\Models\\TravelQuote')
//                    ->where(['quote_id' => $travelQuote->id, 'old_primary_member_id' => $travelQuote->primary_member_id])->first();
//
//                if ($customerMemberKey) {
//                    info('tqr old primary_member_id: '.$travelQuote->primary_member_id.' - tqr new primary_member_id:'.$customerMemberKey['id']);
//                    $travelQuote->update([
//                        'primary_member_id' => $customerMemberKey['id'],
//                    ]);
//                }
//            }
//        });
    }
}
