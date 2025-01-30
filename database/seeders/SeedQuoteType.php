<?php

namespace Database\Seeders;

use App\Models\QuoteType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SeedQuoteType extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(WithoutModelEvents::class);

        $quoteTypes = [
            [
                'short_code' => 'SAV',
                'code' => 'Savings',
                'text' => 'Savings Insurance',
                'is_active' => 1,
            ],
        ];

        foreach ($quoteTypes as $quoteType) {
            QuoteType::create($quoteType);
        }
    }
}
