<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
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
    }
}
