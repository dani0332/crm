<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddCapAdvisorPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adviosrCapPermission = Permission::where('name', PermissionsEnum::ADVISOR_CAPACITY_MANAGEMENT)->first();
        if ($adviosrCapPermission == null) {
            DB::table('permissions')->insert([
                'name' => PermissionsEnum::ADVISOR_CAPACITY_MANAGEMENT,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $leadPoolRoleId = Role::where('name', RolesEnum::LeadPool)->first()->id;
        $capLeadAllocationPermissionId = Permission::where('name', PermissionsEnum::ADVISOR_CAPACITY_MANAGEMENT)->first()->id;

        if (count(DB::table('role_has_permissions')->where('role_id', $leadPoolRoleId)->where('permission_id', $capLeadAllocationPermissionId)->get()) == 0) {

            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $capLeadAllocationPermissionId,
                ]);
        }
    }
}
