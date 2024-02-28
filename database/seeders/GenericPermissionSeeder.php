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
        $reportManagement = Permission::where('name', PermissionsEnum::REPORT_MANAGEMENT)->first();
        if (! $reportManagement) {
            Permission::create([
                'name' => PermissionsEnum::REPORT_MANAGEMENT,
                'guard_name' => 'web',
            ]);
        }

        // Add Compliance Permission to Admin
        $role = Role::where('name', RolesEnum::Admin)->first();

        if (! $role->hasPermissionTo(PermissionsEnum::REPORT_MANAGEMENT)) {
            $role->givePermissionTo(PermissionsEnum::REPORT_MANAGEMENT);
        }

    }
}
