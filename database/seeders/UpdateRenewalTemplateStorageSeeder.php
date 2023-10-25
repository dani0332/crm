<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class UpdateRenewalTemplateStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApplicationStorage::whereIn('key_name', ['SIB_CAR_QUOTE_ONE_CLICK_BUY_SINGLE_PLAN_TEMPLATE', 'SIB_CAR_QUOTE_ONE_CLICK_BUY_MULTIPLE_PLAN_TEMPLATE'])->update([
            'value' => '491',
        ]);
    }
}
