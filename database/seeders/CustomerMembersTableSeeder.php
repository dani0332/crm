<?php

namespace Database\Seeders;

use App\Models\KycLog;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use App\Enums\CustomerTypeEnum;
use Illuminate\Database\Seeder;
use App\Models\HealthMemberDetail;
use App\Models\TravelMemberDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerMembersTableSeeder extends Seeder
{
    public $totalDuplicate = 0;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HealthMemberDetail::chunk(500, function ($healthMemberDetails) {
            foreach ($healthMemberDetails as $hqrmd) {
                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $hqrmd->health_quote_request_id)
                    ->where('quote_type', HealthQuote::class)
                    ->where('customer_entity_id', $hqrmd->customer_id)
                    ->where('code', $hqrmd->code)
                    ->exists();

                Log::info('hqrmd id: ' . $hqrmd->id);
                Log::info('isDuplicate: ' . $isDuplicate);

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
                }else{
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

                Log::info('tqrmd id: ' . $tqrmd->id);
                Log::info('isDuplicate: ' . $isDuplicate);

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
                    ]);
                }else{
                    $this->totalDuplicate++;
                }
            }
        });

        KycLog::withTrashed()->chunk(100, function ($kycLogs){
            foreach ($kycLogs as $kycLog)
            {
                if ($kycLog->match_found != null && $kycLog->match_found == 0)
                {
                    $kycLog->update([
                        'decision' => 'Pass'
                    ]);
                }
            }
        });

        Log::info('total duplicats: ' . $this->totalDuplicate);

    }
}
