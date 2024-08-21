<?php

namespace Database\Seeders;

use App\Enums\GenericRequestEnum;
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

        CarMake::updateOrCreate(['text' => GenericRequestEnum::MOTOR_BIKE], [
            'code' => $carMakeCode,
            'text_ar' => GenericRequestEnum::MOTOR_BIKE,
            'axa_car_make' => GenericRequestEnum::MOTORBIKE,
            'is_active' => 0,
        ]);
    }
}
