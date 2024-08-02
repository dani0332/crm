<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AddRenewalTemplateStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        ApplicationStorage::updateOrCreate(
            ['key_name' => 'SIB_BIKE_QUOTE_PLAN_TEMPLATE'],
            [
                'key_name' => 'SIB_BIKE_QUOTE_PLAN_TEMPLATE',
                'value' => '586',
                'is_active' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'created_by' => 'muhammad.waris@insurancemarket.ae',
                'updated_by' => 'muhammad.waris@insurancemarket.ae',
            ]
        );
    }
}
