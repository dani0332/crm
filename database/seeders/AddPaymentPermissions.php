<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPaymentPermissions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', RolesEnum::Admin)->first();

        
        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_DISCOUNT_EDIT, 'web');

        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
        
        
        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_DISCOUNT_ADD, 'web');

        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_CREDIT_APPROVAL_ADD, 'web');
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_FREQUENCY_UPRONT_SPLIT_COLLECTED_BY_BROKER_ADD, 'web');
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_BROKER_ADD, 'web');
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_INSURER_ADD, 'web');
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_BROKER, 'web');
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRole->id,
                    'permission_id' => $permission->id,
                ]
            );
        }

        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_INSURER, 'web');
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
