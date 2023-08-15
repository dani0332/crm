<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class addTeamThresholdViewPermission extends Seeder
{
    /**
     * Run the database seeds .
     *
     * @return void
     */
    public function run()
    {
        $permissions[] = Permission::findOrCreate(PermissionsEnum::TeamThresholdView, 'web')->id;

        $adminRole = Role::findOrCreate(RolesEnum::Admin, 'web');
        foreach ($permissions as $permission) {
            if (! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }
    }
}
