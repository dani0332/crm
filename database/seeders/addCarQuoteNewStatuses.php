<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addCarQuoteNewStatuses extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $awaitingQuote = QuoteStatus::where('id', QuoteStatusEnum::AwaitingQuote)->first();

        if ($awaitingQuote) {
            $petFakedMap = DB::table('quote_status_map')->where(['quote_type_id' => 9, 'quote_status_id' => 9])->first();
            if (! $petFakedMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => QuoteStatusEnum::AwaitingQuote,
                    'sort_order' => 21,
                    'created_by' => 'ahsan.ashfaq@insurancemarket.ae',
                    'updated_by' => 'ahsan.ashfaq@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
