<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->paidLeads();
        // $this->seedProcessTrackerPermissions();
        // $this->buyLeads();
        // $this->leadAllocationDashboards();\
        $this->impersonate();
    }

    private function impersonate()
    {
        Permission::findOrCreate(PermissionsEnum::ENABLE_IMPERSONATION, 'web');
    }

    private function leadAllocationDashboards()
    {
        Permission::findOrCreate(PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD, 'web');
        Permission::findOrCreate(PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD, 'web');
        Permission::findOrCreate(PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD, 'web');
        Permission::findOrCreate(PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD, 'web');
        Permission::findOrCreate(PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD, 'web');
        Permission::findOrCreate(PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD, 'web');
    }

    private function paidLeads()
    {
        Permission::findOrCreate(PermissionsEnum::ASSIGN_PAID_LEADS, 'web');
    }

    private function seedProcessTrackerPermissions()
    {
        $permission = Permission::findOrCreate(PermissionsEnum::VIEW_PROCESS_TRACKER, 'web');

        // Assign to Engineering Role
        $role = Role::where('name', RolesEnum::Engineering)->first();
        if ($role && ! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    private function buyLeads()
    {
        Permission::findOrCreate(PermissionsEnum::BUY_LEADS, 'web');
    }
}
