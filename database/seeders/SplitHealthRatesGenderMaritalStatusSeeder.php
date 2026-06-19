<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SplitHealthRatesGenderMaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement("
        UPDATE health_rates
        SET
            marital_status = CASE
                WHEN gender = 'FS' THEN 'SINGLE'
                WHEN gender = 'FM' THEN 'MARRIED'
                ELSE NULL
            END,
            gender = CASE
                WHEN gender = 'M' THEN 'MALE'
                WHEN gender IN ('FS', 'FM') THEN 'FEMALE'
                ELSE NULL
            END
        WHERE gender IN ('M', 'FS', 'FM')
     ");
    }
}
