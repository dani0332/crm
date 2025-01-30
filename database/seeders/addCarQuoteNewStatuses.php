<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
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
        $pendingQuote = QuoteStatus::where('id', QuoteStatusEnum::PendingQuote)->first();
        if (! $pendingQuote) {
            DB::table('quote_status')->insert([
                'id' => 51,
                'text' => 'Pending Quote',
                'code' => 'PendingQuote',
                'sort_order' => 14,
                'is_active' => 1,
                'created_by' => 'ahsan.ashfaq@insurancemarket.ae',
                'updated_by' => 'ahsan.ashfaq@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $pendingQuote = QuoteStatus::where('id', QuoteStatusEnum::PendingQuote)->first();
        }
        if ($pendingQuote) {
            $pendingQuoteMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => QuoteStatusEnum::PendingQuote])->first();
            if (! $pendingQuoteMap) {
                DB::table('quote_status_map')->insert([
                    'quote_type_id' => 1,
                    'quote_status_id' => QuoteStatusEnum::PendingQuote,
                    'sort_order' => 14,
                    'created_by' => 'ahsan.ashfaq@insurancemarket.ae',
                    'updated_by' => 'ahsan.ashfaq@insurancemarket.ae',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                QuoteStatusMap::where('quote_status_id', QuoteStatusEnum::Lost)->increment('sort_order');
                QuoteStatusMap::where('quote_status_id', QuoteStatusEnum::Duplicate)->increment('sort_order');
                QuoteStatusMap::where('quote_status_id', QuoteStatusEnum::Fake)->increment('sort_order');
                QuoteStatusMap::where('quote_status_id', QuoteStatusEnum::TransactionApproved)->increment('sort_order');
            }
        }

        // todo: check sort order
        if (! QuoteStatus::where('id', QuoteStatusEnum::CarSold)->first()) {
            $carSold = QuoteStatus::create([
                'id' => QuoteStatusEnum::CarSold,
                'text' => 'Car Sold',
                'text_ar' => 'Car Sold',
                'code' => 'CarSold',
                'sort_order' => 19,
                'is_active' => 1,
                'created_by' => 'faisal.abbas@insurancemarket.ae',
                'updated_by' => 'faisal.abbas@insurancemarket.ae',
            ]);

            $carSold->quoteStatusMap()->create([
                'quote_type_id' => QuoteTypeId::Car,
                'sort_order' => 19,
                'created_by' => 'faisal.abbas@insurancemarket.ae',
                'updated_by' => 'faisal.abbas@insurancemarket.ae',
            ]);
        }

        if (! QuoteStatus::where('id', QuoteStatusEnum::Uncontactable)->first()) {
            $carSold = QuoteStatus::create([
                'id' => QuoteStatusEnum::Uncontactable,
                'text' => 'Uncontactable',
                'text_ar' => 'Uncontactable',
                'code' => 'uncontactable',
                'sort_order' => 20,
                'is_active' => 1,
                'created_by' => 'faisal.abbas@insurancemarket.ae',
                'updated_by' => 'faisal.abbas@insurancemarket.ae',
            ]);

            $carSold->quoteStatusMap()->create([
                'quote_type_id' => QuoteTypeId::Car,
                'sort_order' => 20,
                'created_by' => 'faisal.abbas@insurancemarket.ae',
                'updated_by' => 'faisal.abbas@insurancemarket.ae',
            ]);
        }
    }
}
