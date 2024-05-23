<?php

namespace Database\Seeders;

use App\Models\LostReasons;
use Illuminate\Database\Seeder;

class LostReasonsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LostReasons::updateOrCreate(['text' => 'Stale for more than 90 days'], ['text_ar' => 'Stale for more than 90 days']);
    }
}
