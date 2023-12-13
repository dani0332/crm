<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use App\Models\QuoteStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AutofollowupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! QuoteStatus::where('code', QuoteStatusEnum::Stale)->first()) {

            $quoteStatus = QuoteStatus::create([
                'text' => 'Stale',
                'code' => QuoteStatusEnum::Stale,
                'sort_order' => 22,
                'is_active' => 1,
                'created_by' => 'faisal.abbas@insurancemarket.ae',
                'updated_by' => 'faisal.abbas@insurancemarket.ae',
            ]);

            DB::table('quote_status_map')->insert([
                'quote_type_id' => QuoteTypeId::Car,
                'quote_status_id' => $quoteStatus->id,
                'sort_order' => 22,
                'created_by' => 'faisal.abbas@insurancemarket.ae',
                'updated_by' => 'faisal.abbas@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_AUTO_FOLLOWUP)->first()) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::ENABLE_AUTO_FOLLOWUP,
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
