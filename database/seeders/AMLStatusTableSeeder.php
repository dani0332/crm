<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AMLStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // AML Pending
        $amlPending = DB::table('aml_status')->where('code', 'AMLPending')->first();
        if (! $amlPending) {
            DB::table('aml_status')->insert([
                'id' => 1,
                'code' => 'AMLPending',
                'text' => 'AML Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }

        // AML Screening Cleared
        $amlScreeningCleared = DB::table('aml_status')->where('code', 'AMLScreeningCleared')->first();
        if (! $amlScreeningCleared) {
            DB::table('aml_status')->insert([
                'id' => 2,
                'code' => 'AMLScreeningCleared',
                'text' => 'AML Screening Cleared',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }

        // AML Screening Failed
        $amlScreeningFailed = DB::table('aml_status')->where('code', 'AMLScreeningFailed')->first();
        if (! $amlScreeningFailed) {
            DB::table('aml_status')->insert([
                'id' => 3,
                'code' => 'AMLScreeningFailed',
                'text' => 'AML Screening Failed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
    }
}
