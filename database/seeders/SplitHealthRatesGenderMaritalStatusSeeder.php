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
             gender = CASE
                 WHEN gender = 'M' THEN 'Male'
                 WHEN gender IN ('FS', 'FM') THEN 'Female'
                 ELSE NULL
             END,
     
             marital_status = CASE
                 WHEN gender = 'FS' THEN 'Single'
                 WHEN gender = 'FM' THEN 'Married'
                 ELSE NULL
             END
     
         WHERE gender IN ('M', 'FS', 'FM')
     ");
    }
}
