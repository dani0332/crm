<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class PqaLeadAllocationPermissionSeeder extends Seeder
{
    /**
     * Pre Qualification Advisor (PQA) ILA permissions and role.
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $this->ensurePermissions();
        $this->ensurePreQualificationAdvisorRole();
        $this->ensurePreQualificationLeadRole();
        $this->assignDashboardToOperatorRoles();
    }

    private function ensurePermissions(): void
    {
        foreach ([
            PermissionsEnum::PQA_LEAD_ALLOCATION_DASHBOARD,
            PermissionsEnum::PQA_LEAD_ALLOCATION_VIEW_ONLY,
            PermissionsEnum::PQA_LEAD_ALLOCATION_EDIT,
            PermissionsEnum::ASSIGN_GROUP_MEDICAL_PRE_QUALIFICATION_ADVISOR,
        ] as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function ensurePreQualificationAdvisorRole(): void
    {
        $role = Role::firstOrCreate(
            [
                'name' => RolesEnum::PreQualificationAdvisor,
                'guard_name' => 'web',
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->givePermission($role, PermissionsEnum::PQA_LEAD_ALLOCATION_DASHBOARD);
        $this->givePermission($role, PermissionsEnum::PQA_LEAD_ALLOCATION_VIEW_ONLY);
    }

    private function ensurePreQualificationLeadRole(): void
    {
        $role = Role::firstOrCreate(
            [
                'name' => RolesEnum::PreQualificationLead,
                'guard_name' => 'web',
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->givePermission($role, PermissionsEnum::ASSIGN_GROUP_MEDICAL_PRE_QUALIFICATION_ADVISOR);
    }

    private function assignDashboardToOperatorRoles(): void
    {
        $roleNames = [
            RolesEnum::Admin,
            RolesEnum::Engineering,
            RolesEnum::LeadPool,
            RolesEnum::ManagerLeadAllocation,
            RolesEnum::ManagerLeadAllocationEdit,
        ];

        $permission = Permission::query()
            ->where('name', PermissionsEnum::PQA_LEAD_ALLOCATION_DASHBOARD)
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            LoggerService::error('PQA lead allocation dashboard permission missing after ensurePermissions()');

            return;
        }

        $roles = Role::query()->whereIn('name', $roleNames)->where('guard_name', 'web')->get();

        foreach ($roles as $role) {
            $this->givePermission($role, PermissionsEnum::PQA_LEAD_ALLOCATION_DASHBOARD);
        }
    }

    private function givePermission(Role $role, string $permissionName): void
    {
        if (! $role->hasPermissionTo($permissionName)) {
            $role->givePermissionTo($permissionName);
            LoggerService::info("PQA: permission {$permissionName} assigned to role {$role->name}");
        }
    }
}
