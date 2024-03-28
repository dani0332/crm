<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatusMap;
use Illuminate\Database\Seeder;

class QuoteStatusMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quoteStatuses = [
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'quote_status_id' => QuoteStatusEnum::CancellationPending,
                'sort_order' => 9,
                'created_by' => 'mirza.baig@myalfred.com',
                'updated_by' => 'mirza.baig@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Life,
                'quote_status_id' => QuoteStatusEnum::CancellationPending,
                'sort_order' => 9,
                'created_by' => 'mirza.baig@myalfred.com',
                'updated_by' => 'mirza.baig@myalfred.com',
            ],
        ];

        foreach ($quoteStatuses as $quoteStatus) {
            QuoteStatusMap::updateOrCreate($quoteStatus);
        }
    }
}
