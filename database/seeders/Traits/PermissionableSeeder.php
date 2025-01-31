<?php

namespace Database\Seeders\Traits;

use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\Models\Permission as ModelsPermission;
use Spatie\Permission\Models\Role as ModelsRole;

trait PermissionableSeeder
{
    private function seedRoles(array $roles)
    {
        foreach ($roles as $role) {
            $this->createRole($role);
        }
    }

    private function createRole(string $role)
    {
        return Role::firstOrCreate(['name' => $role]);
    }

    private function seedPermissions(array $permissions, array $rolesToAssign = [])
    {
        foreach ($permissions as $permission) {
            $this->createPermission($permission, $rolesToAssign);
        }
    }

    private function createPermission(string $permissionName, array $rolesToAssign = [])
    {
        $permission = Permission::findOrCreate($permissionName, 'web');

        $roles = Role::whereIn('name', $rolesToAssign)->get();
        foreach ($roles as $role) {
            $this->assignPermissionToRole($role, $permission);
        }
        
        return $permission;
    }

    private function assignPermissionToRole(Role|ModelsRole $role = null, Permission|ModelsPermission $permission = null)
    {
        if(!$role || !$permission) {
            return;
        }

        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }
}
