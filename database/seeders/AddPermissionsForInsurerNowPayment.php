<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPermissionsForInsurerNowPayment extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inplUserPermission = Permission::findOrCreate(PermissionsEnum::INPL_USER, 'web');
        $inplApproverPermission = Permission::findOrCreate(PermissionsEnum::INPL_APPROVER, 'web');

        // Add INPL_USER permissions for specific roles
        $roles = [RolesEnum::Admin, RolesEnum::OperationExecutive, RolesEnum::ServiceExecutive, RolesEnum::CarAdvisor,
            RolesEnum::CarManager, RolesEnum::TravelManager, RolesEnum::TravelAdvisor,
            RolesEnum::HomeManager, RolesEnum::HomeAdvisor, RolesEnum::BikeManager,
            RolesEnum::BikeAdvisor, RolesEnum::CorpLineAdvisor, RolesEnum::CorplineManager];
        foreach ($roles as $role) {
            $adminRole = Role::where('name', $role)->first();

            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $inplUserPermission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $inplUserPermission->id,
                    ]
                );
            }
        }

        // Add INPL_APPROVER permissions for Admin and PA
        $roles = [RolesEnum::Admin, RolesEnum::PA];
        foreach ($roles as $role) {
            $adminRole = Role::where('name', $role)->first();
            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $inplApproverPermission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $inplApproverPermission->id,
                    ]
                );
            }
        }
    }
}
