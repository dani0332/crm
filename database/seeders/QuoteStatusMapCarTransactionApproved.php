<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteStatusMapCarTransactionApproved extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // CAR - START

        // New Lead
        $carTransactionApprovedMap = DB::table('quote_status_map')->where(['quote_type_id' => 1, 'quote_status_id' => 15])->first();
        if (! $carTransactionApprovedMap) {
            DB::table('quote_status_map')->insert([
                'quote_type_id' => 1,
                'quote_status_id' => 15,
                'sort_order' => 17,
                'created_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'updated_by' => 'muhammad.shajiuddin@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // CAR - END
    }
}
