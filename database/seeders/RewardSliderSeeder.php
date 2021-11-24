<?php

namespace Database\Seeders;

use App\Models\RewardSlider;
use Illuminate\Database\Seeder;

class RewardSliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RewardSlider::factory()->count(20)->create();
        //dd('here');
        // factory(RewardSlider::class, 20)->create();
    }
}
