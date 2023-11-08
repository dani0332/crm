<?php

namespace Database\Seeders;

use App\Models\HealthQuote;
use App\Models\TravelQuote;
use Illuminate\Database\Seeder;
use App\Models\HealthMemberDetail;
use App\Models\TravelMemberDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomerMembersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HealthMemberDetail::chunk(100, function ($healthMemberDetails) {
            foreach ($healthMemberDetails as $hqrmd) {
                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $hqrmd->health_quote_request_id)
                    ->where('quote_type', HealthQuote::class)
                    ->where('customer_id', $hqrmd->customer_id)
                    ->exists();

                if (!$isDuplicate) {
                    DB::table('customer_members')->insert([
                        'quote_type' => HealthQuote::class,
                        'quote_id'  => $hqrmd->health_quote_request_id ?? null,
                        'customer_id' => $hqrmd->customer_id ?? null,
                        'code' => $hqrmd->code ?? null,
                        'first_name' => $hqrmd->first_name ?? null,
                        'last_name' => $hqrmd->last_name ?? null,
                        'gender' => $hqrmd->gender ?? null,
                        'dob' => $hqrmd->dob ?? null,
                        'nationality_id' => $hqrmd->nationality_id ?? null,
                        'policy_id' => $hqrmd->policy_id ?? null,
                        'member_category_id' => $hqrmd->member_category_id ?? null,
                        'salary_band_id' => $hqrmd->salary_band_id ?? null,
                        'emirate_of_your_visa_id' => $hqrmd->emirate_of_your_visa_id ?? null,
                        'relation_code' => $hqrmd->relation_code ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        // $this->command->info('Health Quote Request Member Details seeded successfully.');

        TravelMemberDetail::chunk(100, function ($travelMemberDetails) {
            foreach ($travelMemberDetails as $tqrmd) {
                $isDuplicate = DB::table('customer_members')
                    ->where('quote_id', $tqrmd->travel_quote_request_id)
                    ->where('quote_type', TravelQuote::class)
                    ->where('customer_id', $tqrmd->customer_id)
                    ->exists();

                if (!$isDuplicate) {
                    DB::table('customer_members')->insert([
                        'quote_type' => TravelQuote::class,
                        'quote_id'  => $tqrmd->travel_quote_request_id ?? null,
                        'customer_id' => $tqrmd->customer_id ?? null,
                        'code' => $tqrmd->code ?? null,
                        'first_name' => $tqrmd->first_name ?? null,
                        'last_name' => $tqrmd->last_name ?? null,
                        'gender' => $tqrmd->gender ?? null,
                        'dob' => $tqrmd->dob ?? null,
                        'nationality_id' => $tqrmd->nationality_id ?? null,
                        'policy_id' => $tqrmd->policy_id ?? null,
                        'member_category_id' => $tqrmd->member_category_id ?? null,
                        'salary_band_id' => $tqrmd->salary_band_id ?? null,
                        'emirate_of_your_visa_id' => $tqrmd->emirate_of_your_visa_id ?? null,
                        'relation_code' => $tqrmd->relation_code ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
