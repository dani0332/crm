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

        if (($storage = ApplicationStorage::where('key_name', 'SIB_CAR_QUOTE_ONE_CLICK_BUY_SINGLE_PLAN_TEMPLATE')->first()) && ($storage->value != '491')) {
            $storage->update(['value' => '491']);
        }

        if (($storage = ApplicationStorage::where('key_name', 'SIB_CAR_QUOTE_ONE_CLICK_BUY_MULTIPLE_PLAN_TEMPLATE')->first()) && ($storage->value != '491')) {
            $storage->update(['value' => '491']);
        }

        if (($storage = ApplicationStorage::where('key_name', 'SIB_CAR_QUOTE_ONE_CLICK_BUY_ZERO_PLAN_TEMPLATE')->first()) && ($storage->value != '492')) {
            $storage->update(['value' => '492']);
        }

        if (($storage = ApplicationStorage::where('key_name', 'SIB_BIKE_QUOTE_ONE_CLICK_BUY_SINGLE_PLAN_TEMPLATE')->first()) && ($storage->value != '493')) {
            $storage->update(['value' => '493']);
        }

        if (($storage = ApplicationStorage::where('key_name', 'SIB_BIKE_QUOTE_ONE_CLICK_BUY_MULTIPLE_PLAN_TEMPLATE')->first()) && ($storage->value != '494')) {
            $storage->update(['value' => '494']);
        }

        if (($storage = ApplicationStorage::where('key_name', 'SIB_BIKE_QUOTE_ONE_CLICK_BUY_ZERO_PLAN_TEMPLATE')->first()) && ($storage->value != '495')) {
            $storage->update(['value' => '495']);
        }
    }
}
