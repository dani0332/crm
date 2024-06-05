<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddTempUpdateTotalPricePermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = Permission::findOrCreate(PermissionsEnum::TEMP_UPDATE_TOTALPRICE, 'web');
        $roles = ['_ADVISOR', '_MANAGER'];
        $lobs = [
            QuoteTypes::CAR->value,
            QuoteTypes::HOME->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::BUSINESS->value,
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ];

        foreach ($lobs as $lob) {
            foreach ($roles as $role) {
                $role = Role::findOrCreate(strtoupper($lob).$role, 'web');
                $role->givePermissionTo($permission->id);
            }
        }
    }
}
