<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;

class ILAGMPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    $permission = Permission::firstOrCreate(['name' => PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD]);
    $roles = Role::whereIn('name', [RolesEnum::Engineering, RolesEnum::Admin])->get();
    foreach ($roles as $role) {
        if (!$role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }
    }
}
