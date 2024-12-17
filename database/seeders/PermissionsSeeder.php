<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->paidLeads();
        $this->buyLeads();
        $this->leadAllocationDashboards();
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

    private function buyLeads()
    {
        Permission::findOrCreate(PermissionsEnum::BUY_LEADS, 'web');
    }
}
