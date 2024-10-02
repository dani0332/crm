<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use App\Enums\RolesEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->paidLeads();
        $this->seedProcessTrackerPermissions();
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
}
