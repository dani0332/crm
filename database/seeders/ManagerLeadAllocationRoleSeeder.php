<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ManagerLeadAllocationRoleSeeder extends Seeder
{
    /**
     * Idempotent: safe to run multiple times (firstOrCreate / hasPermissionTo checks).
     */
    public function run(): void
    {
        $this->seedViewOnlyRole();
        $this->seedEditRole();
    }

    private function seedViewOnlyRole(): void
    {
        $role = Role::firstOrCreate(
            [
                'name' => RolesEnum::ManagerLeadAllocation,
                'guard_name' => 'web',
            ]
        );

        $permissionNames = [
            PermissionsEnum::CAR_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::CYCLE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::DEVICE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::LIFE_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::PET_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::YACHT_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::HOME_LEAD_ALLOCATION_VIEW_ONLY,
        ];

        $this->assignPermissionsToRole($role, $permissionNames);
    }

    private function seedEditRole(): void
    {
        $role = Role::firstOrCreate(
            [
                'name' => RolesEnum::ManagerLeadAllocationEdit,
                'guard_name' => 'web',
            ]
        );

        $this->assignPermissionsToRole($role, [
            PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::CYBER_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::CYCLE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::DEVICE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::LIFE_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::PET_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::YACHT_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::HOME_LEAD_ALLOCATION_EDIT,
        ]);
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    private function assignPermissionsToRole(Role $role, array $permissionNames): void
    {
        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
