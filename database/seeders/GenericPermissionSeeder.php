<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class GenericPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $healthManagerAccess = Permission::where('name', PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)->first();
        if (! $healthManagerAccess) {
            Permission::create([
                'name' => PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS,
                'guard_name' => 'web',
            ]);
        }

        $healthQuoteAccess = Permission::where('name', PermissionsEnum::HEALTH_QUOTES_ACCESS)->first();
        if (! $healthQuoteAccess) {
            Permission::create([
                'name' => PermissionsEnum::HEALTH_QUOTES_ACCESS,
                'guard_name' => 'web',
            ]);
        }

        // Book policy permissions & roles
        $bookPolicyPermissions = [
            ['name' => PermissionsEnum::BOOK_POLICY_EDIT, 'role' => RolesEnum::PRODUCTION],
            ['name' => PermissionsEnum::BOOK_POLICY_EDIT, 'role' => RolesEnum::NRA],
            ['name' => PermissionsEnum::BOOK_POLICY_EDIT, 'role' => RolesEnum::FINANCE],
        ];

        foreach ($bookPolicyPermissions as $permission) {
            $permissionRecord = Permission::where('name', $permission['name'])->first();

            if (! $permissionRecord) {
                $permissionRecord = Permission::create([
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ]);
            }

            if (! empty($permission['role'])) {
                $role = Role::where('name', $permission['role'])->first();

                if (! $role) {
                    $role = Role::create([
                        'name' => $permission['role'],
                        'guard_name' => 'web',
                    ]);
                }

                if (! $role->hasPermissionTo($permissionRecord->id)) {
                    $role->givePermissionTo($permissionRecord->id);
                }
            }
        }
        // Add Compliance Permission to Admin
        $role = Role::where('name', RolesEnum::Admin)->first();

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS);
        }

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS);
        }
    }
}
