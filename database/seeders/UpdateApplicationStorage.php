<?php

namespace Database\Seeders;

use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Models\Team;
use Illuminate\Database\Seeder;

class UpdateApplicationStorage extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $saturday = ApplicationStorage::where('key_name', 'SATURDAY_CAP_RESET_TIME')->first();
        $sunday = ApplicationStorage::where('key_name', 'SUNDAY_CAP_RESET_TIME')->first();
        if (! $saturday && $sunday) {
            $sundayResetTime = ApplicationStorage::where('key_name', 'SUNDAY_CAP_RESET_TIME')->first();
            if ($sundayResetTime) {
                $sundayResetTime->key_name = 'SATURDAY_CAP_RESET_TIME';
                $sundayResetTime->save();
            }
        }
        $carProduct = Team::where('name', quoteTypeCode::Car)->first();
        if ($carProduct->type != 1) {
            Team::where('name', quoteTypeCode::Car)->update(['type' => 1]);
            Team::where('name', quoteTypeCode::Home)->update(['type' => 1]);
            Team::where('name', quoteTypeCode::Life)->update(['type' => 1]);
            Team::where('name', quoteTypeCode::Pet)->update(['type' => 1]);
            Team::where('name', quoteTypeCode::Business)->update(['type' => 1]);
            Team::where('name', quoteTypeCode::Health)->update(['type' => 1]);
            Team::where('name', quoteTypeCode::Travel)->update(['type' => 1]);
        }

        $carLeadPickupLimit = ApplicationStorage::where('key_name', 'CAR_LEAD_PICKUP_LIMIT')->first();
        if ($carLeadPickupLimit == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_LEAD_PICKUP_LIMIT',
                'value' => '100',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
