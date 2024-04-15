<?php

namespace Database\Seeders;

use App\Models\CarMake;
use Illuminate\Database\Seeder;

class CarMakeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carMakeDetails = CarMake::latest()->first();
        $carMakeCode = ($carMakeDetails->code + 1);

        CarMake::updateOrCreate(['text' => 'MOTOR BIKE'], [
            'code' => $carMakeCode,
            'text_ar' => 'MOTOR BIKE',
            'axa_car_make' => 'MOTORBIKE',
            'is_active' => 0,
        ]);
    }
}
