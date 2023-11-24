<?php

namespace App\Console\Commands;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class runmigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:runmigration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        HealthQuote::whereNotNull('primary_member_id')->chunkById(1000, function ($healthQuotes) {
            foreach ($healthQuotes as $healthQuote) {
                $customerMemberKey = CustomerMembers::whereHasMorph('quote', '\\App\\Models\\HealthQuote')
                    ->where(['quote_id' => $healthQuote->id, 'old_primary_member_id' => $healthQuote->primary_member_id])->first();

                if ($customerMemberKey) {
                    info('hqr old primary_member_id: '.$healthQuote->primary_member_id.' - hqr new primary_member_id:'.$customerMemberKey['id']);
                    $healthQuote->update([
                        'primary_member_id' => $customerMemberKey['id'],
                    ]);
                }
            }
        });

        TravelMemberDetail::chunk(1000, function ($travelMemberDetails) {
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
                }
            }
        });

        TravelQuote::whereNotNull('primary_member_id')->chunkById(1000, function ($travelQuotes) {
            foreach ($travelQuotes as $travelQuote) {
                $customerMemberKey = CustomerMembers::whereHasMorph('quote', '\\App\\Models\\TravelQuote')
                    ->where(['quote_id' => $travelQuote->id, 'old_primary_member_id' => $travelQuote->primary_member_id])->first();

                if ($customerMemberKey) {
                    info('tqr old primary_member_id: '.$travelQuote->primary_member_id.' - tqr new primary_member_id:'.$customerMemberKey['id']);
                    $travelQuote->update([
                        'primary_member_id' => $customerMemberKey['id'],
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

        info('customer member migration job completed');
    }
}
