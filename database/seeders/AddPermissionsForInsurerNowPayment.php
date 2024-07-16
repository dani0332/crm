<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addPermissionsForInsurerNowPayment extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::findOrCreate(PermissionsEnum::INPL_USER, 'web');
        Permission::findOrCreate(PermissionsEnum::INPL_APPROVER, 'web');

        // Add INPL_USER permissions for specific roles
        $roles = [RolesEnum::Admin, RolesEnum::OperationExecutive, RolesEnum::ServiceExecutive, RolesEnum::CarAdvisor,
            RolesEnum::CarManager, RolesEnum::TravelManager, RolesEnum::TravelAdvisor,
            RolesEnum::HomeManager, RolesEnum::HomeAdvisor, RolesEnum::BikeManager,
            RolesEnum::BikeAdvisor, RolesEnum::CorpLineAdvisor, RolesEnum::CorplineManager];
        foreach ($roles as $role) {
            $adminRole = Role::where('name', $role)->first();
            $permission = Permission::findOrCreate(PermissionsEnum::INPL_USER, 'web');

            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $permission->id,
                    ]
                );
            }
        }

        // Add INPL_APPROVER permissions for Admin and PA
        $roles = [RolesEnum::Admin, RolesEnum::PA];
        foreach ($roles as $role) {
            $adminRole = Role::where('name', $role)->first();
            $permission = Permission::findOrCreate(PermissionsEnum::INPL_APPROVER, 'web');

            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $permission->id,
                    ]
                );
            }
        }
    }
}
