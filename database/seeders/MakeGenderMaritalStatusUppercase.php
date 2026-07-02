<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MakeGenderMaritalStatusUppercase extends Seeder
{
    public function run(): void
    {
        DB::statement('
            UPDATE health_rates
            SET gender = UPPER(gender), marital_status = UPPER(marital_status)
        ');
    }
}
