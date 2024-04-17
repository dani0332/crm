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

        $this->generateSegmentFilterPermission();
    }

    private function generateSegmentFilterPermission()
    {
        $segmentFilterPermission = Permission::where('name', PermissionsEnum::SEGMENT_FILTER)->first();
        if (! $segmentFilterPermission) {
            Permission::create([
                'name' => PermissionsEnum::SEGMENT_FILTER,
                'guard_name' => 'web',
            ]);
        }

        $roles = Role::whereIn('name', [RolesEnum::LeadPool, RolesEnum::CarManager, RolesEnum::Admin])->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo(PermissionsEnum::SEGMENT_FILTER)) {
                $role->givePermissionTo(PermissionsEnum::SEGMENT_FILTER);
            }
        }
    }
}
