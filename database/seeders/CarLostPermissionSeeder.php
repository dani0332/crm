<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CarLostPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [];

        $permissions[] = Permission::findOrCreate(PermissionsEnum::CAR_SOLD_LIST, 'web')->id;
        $permissions[] = Permission::findOrCreate(PermissionsEnum::CAR_UNCONTACTABLE_LIST, 'web')->id;

        $moRole = Role::findOrCreate(RolesEnum::MarketingOperations, 'web');
        $moRole->givePermissionTo($permissions);
    }
}
