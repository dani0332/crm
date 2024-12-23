<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\QuoteType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $quoteTypes = DB::table('quote_type')->get();

        foreach ($quoteTypes as $quoteType) {
            if (! $quoteType->short_code) {
                $shortCode = strtoupper(substr($quoteType->code, 0, 3));
                DB::table('quote_type')->where('id', $quoteType->id)->update(['short_code' => $shortCode]);
            }
        }

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
