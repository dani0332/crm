<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddTempUpdatePaymentPermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        $role = Role::findOrCreate(RolesEnum::Admin, 'web');
        $perm = Permission::findOrCreate(PermissionsEnum::TEMP_UPDATE_PAYMENT, 'web');
        if (! $role->hasPermissionTo($perm)) {
            $role->givePermissionTo($perm);
        }
    }
}
