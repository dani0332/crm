<?php

namespace Database\Seeders;

use App\Models\CarQuote;
use Illuminate\Database\Seeder;

class CarQuoteRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CarQuote::factory()->count(5000)->create();
    }
}
