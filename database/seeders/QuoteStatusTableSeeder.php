<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\QuoteStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $lobs = [
            QuoteTypes::CAR->id(),
            QuoteTypes::HOME->id(),
            QuoteTypes::HEALTH->id(),
            QuoteTypes::LIFE->id(),
            QuoteTypes::BUSINESS->id(),
            QuoteTypes::BIKE->id(),
            QuoteTypes::YACHT->id(),
            QuoteTypes::TRAVEL->id(),
            QuoteTypes::PET->id(),
            QuoteTypes::CYCLE->id(),
            QuoteTypes::JETSKI->id(),
        ];

        // Policy Sent to Customer
        if (! QuoteStatus::where('id', QuoteStatusEnum::PolicySentToCustomer)->first()) {
            QuoteStatus::create([
                'id' => QuoteStatusEnum::PolicySentToCustomer,
                'text' => 'Policy Sent to Customer',
                'text_ar' => 'Policy Sent to Customer',
                'code' => 'PolicySentToCustomer',
                'sort_order' => 19,
                'is_active' => 1,
                'created_by' => 'nouman.hussain@insurancemarket.ae',
                'updated_by' => 'nouman.hussain@insurancemarket.ae',
            ]);
            foreach ($lobs as $key => $item) {

                $mapping = DB::table('quote_status_map')->where(['quote_type_id' => $item, 'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer])->first();
                if (! $mapping) {
                    DB::table('quote_status_map')->insert([
                        'quote_type_id' => $item,
                        'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                        'sort_order' => ++$key,
                        'created_by' => 'nouman.hussain@insurancemarket.ae',
                        'updated_by' => 'nouman.hussain@insurancemarket.ae',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Policy Booked
        if (! QuoteStatus::where('id', QuoteStatusEnum::PolicyBooked)->first()) {
            QuoteStatus::create([
                'id' => QuoteStatusEnum::PolicyBooked,
                'text' => 'Policy Booked',
                'text_ar' => 'Policy Booked',
                'code' => 'PolicyBooked',
                'sort_order' => 20,
                'is_active' => 1,
                'created_by' => 'nouman.hussain@insurancemarket.ae',
                'updated_by' => 'nouman.hussain@insurancemarket.ae',
            ]);
            foreach ($lobs as $key => $item) {

                $mapping = DB::table('quote_status_map')->where(['quote_type_id' => $item, 'quote_status_id' => QuoteStatusEnum::PolicyBooked])->first();
                if (! $mapping) {
                    DB::table('quote_status_map')->insert([
                        'quote_type_id' => $item,
                        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                        'sort_order' => ++$key,
                        'created_by' => 'nouman.hussain@insurancemarket.ae',
                        'updated_by' => 'nouman.hussain@insurancemarket.ae',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }   // Policy Pending
        if (! QuoteStatus::where('id', QuoteStatusEnum::PolicyPending)->first()) {
            QuoteStatus::create([
                'id' => QuoteStatusEnum::PolicyPending,
                'text' => 'Policy Pending',
                'text_ar' => 'Policy Pending',
                'code' => 'PolicyPending',
                'sort_order' => 20,
                'is_active' => 1,
                'created_by' => 'nouman.hussain@insurancemarket.ae',
                'updated_by' => 'nouman.hussain@insurancemarket.ae',
            ]);
            foreach ($lobs as $key => $item) {

                $mapping = DB::table('quote_status_map')->where(['quote_type_id' => $item, 'quote_status_id' => QuoteStatusEnum::PolicyPending])->first();
                if (! $mapping) {
                    DB::table('quote_status_map')->insert([
                        'quote_type_id' => $item,
                        'quote_status_id' => QuoteStatusEnum::PolicyPending,
                        'sort_order' => ++$key,
                        'created_by' => 'nouman.hussain@insurancemarket.ae',
                        'updated_by' => 'nouman.hussain@insurancemarket.ae',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
