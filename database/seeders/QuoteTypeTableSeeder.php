<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\QuoteType;
use Illuminate\Database\Seeder;

class QuoteTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        QuoteType::firstOrCreate(
            ['code' => 'CompanyCar'],
            [
                'id' => QuoteTypeId::CompanyCar,
                'short_code' => "COM",
                'code' => 'CompanyCar',
                'text' => 'Company Car Insurance',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'parent_id' => QuoteTypeId::Car,
            ],
        );
    }
}
