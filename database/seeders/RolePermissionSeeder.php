<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->searchModulePermissions();
    }

    private function searchModulePermissions()
    {
        // Add Search across all LOBs permission
        $searchAcrossLOBsPermissions = [PermissionsEnum::SEARCH_ALL_LEAD_LOB, PermissionsEnum::DATA_EXTRACTION_SEARCH_ALL_LEADS];

        foreach ($searchAcrossLOBsPermissions as $searchAcrossLOBsPermission) {
            $permission = Permission::where('name', $searchAcrossLOBsPermission)->first();

            if (! $permission) {
                Permission::create([
                    'name' => $searchAcrossLOBsPermission,
                    'guard_name' => 'web',
                ]);
                $role = Role::where('name', RolesEnum::Admin)->first();

                if (! $role->hasPermissionTo($searchAcrossLOBsPermission)) {
                    $role->givePermissionTo($searchAcrossLOBsPermission);
                }
            }
        }
    }
}
