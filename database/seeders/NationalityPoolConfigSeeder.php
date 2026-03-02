<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class NationalityPoolConfigSeeder extends Seeder
{
    public function run(): void
    {
        $this->createNationalityPoolConfigPermission();
    }

    private function createNationalityPoolConfigPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::NATIONALITY_POOL_CONFIG,
            'guard_name' => 'web',
        ]);

        $this->assignPermissionToRole(RolesEnum::Admin, $permission);
    }

    private function assignPermissionToRole(string $role, Permission $permission): void
    {
        $role = Role::firstWhere('name', $role);
        if ($role) {
            $role->permissions()->syncWithoutDetaching($permission->id);
        }
    }
}
