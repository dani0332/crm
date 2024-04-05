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

        // Add Compliance Permission to Admin
        $role = Role::where('name', RolesEnum::Admin)->first();

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS);
        }

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS);
        }

        // Plans Selection & Plan Details Section Permissions
        $planDetailsAdd = Permission::where('name', PermissionsEnum::PLAN_DETAILS_ADD)->first();
        if (! $planDetailsAdd) {
            Permission::create([
                'name' => PermissionsEnum::PLAN_DETAILS_ADD,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo(PermissionsEnum::PLAN_DETAILS_ADD);
        }

        $availablePlanSelect = Permission::where('name', PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON)->first();
        if (! $availablePlanSelect) {
            Permission::create([
                'name' => PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo(PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON);
        }
    }
}
