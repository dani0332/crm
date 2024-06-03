<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class BookPolicyPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $roles = [RolesEnum::NRA, RolesEnum::PRODUCTION, RolesEnum::FINANCE];
        $permissions = [PermissionsEnum::VIEW_INSLY_BOOK_POLICY, PermissionsEnum::SEND_INSLY_BOOK_POLICY];

        foreach ($roles as $role) {
            $role = Role::findOrCreate($role, 'web');

            foreach ($permissions as $permission) {
                $perm = Permission::findOrCreate($permission, 'web');
                if (! $role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
        }
    }
}
