<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class EAModelRolePermissionSeeder extends Seeder
{
    /**
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->seedPermissions();
    }

    private function seedRoles(): void
    {
        foreach ([RolesEnum::EAReferral, RolesEnum::EAManager] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            PermissionsEnum::EaCollaborate,
            PermissionsEnum::AssignedExpertAdvisor,
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $adminRoles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        foreach ($adminRoles as $role) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
