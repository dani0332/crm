<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TotalPremiumReportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::SeniorManagement, RolesEnum::CarManager, RolesEnum::Engineering])->get();
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::TOTAL_PREMIUM_LEADS_SALES_REPORT ?? 'total-premium-leads-sales-report',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update permissions for each role
        foreach ($roles as $role) {
            // Check if the role already has the permission
            $record = DB::table('role_has_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->first();

            // If the permission is not assigned to the role, insert it
            if (empty($record)) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]);
            }
        }

    }
}
