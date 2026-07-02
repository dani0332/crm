<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReverseSplitHealthRatesGenderMaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement("
        UPDATE health_rates
        SET
            gender = CASE
                WHEN gender = 'FEMALE' AND marital_status = 'SINGLE' THEN 'FS'
                WHEN gender = 'FEMALE' AND marital_status = 'MARRIED' THEN 'FM'
                WHEN gender = 'MALE' THEN 'M'
                ELSE gender
            END,
            marital_status = CASE
                WHEN gender IN ('M', 'FS', 'FM') THEN NULL
                ELSE marital_status
            END
        WHERE gender IN ('MALE', 'FEMALE')
     ");
    }
}
