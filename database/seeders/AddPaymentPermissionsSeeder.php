<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddPaymentPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionPaymentsCreate = Permission::findOrCreate(PermissionsEnum::PaymentsCreate, 'web');
        $permissionPaymentsEdit = Permission::findOrCreate(PermissionsEnum::PaymentsEdit, 'web');
        $permissionApprovePayments = Permission::findOrCreate(PermissionsEnum::ApprovePayments, 'web');

        // $permissionPlanDetailsAdd = Permission::findOrCreate(PermissionsEnum::PLAN_DETAILS_ADD, 'web');
        // $permissionAvailablePlanSelect = Permission::findOrCreate(PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON, 'web');
        // $permissionTempUpdate = Permission::findOrCreate(PermissionsEnum::TEMP_UPDATE_TOTALPRICE, 'web');

        $roles = ['_ADVISOR', '_MANAGER'];
        $lobs = [
            QuoteTypes::CAR->value,
            QuoteTypes::HOME->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::BUSINESS->value,
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ];

        foreach ($lobs as $lob) {
            foreach ($roles as $role) {
                $role = Role::findOrCreate(strtoupper($lob).$role, 'web');
                $role->givePermissionTo($permissionPaymentsCreate->id);
                $role->givePermissionTo($permissionPaymentsEdit->id);
                // $role->givePermissionTo($permissionPlanDetailsAdd->id);
                // $role->givePermissionTo($permissionAvailablePlanSelect->id);
                // $role->givePermissionTo($permissionTempUpdate->id);
                if ($role == '_MANAGER') {
                    $role->givePermissionTo($permissionApprovePayments->id);
                }
            }
        }

        $productionApprovalRole = Role::findOrCreate('PRODUCTION_APPROVAL', 'web');
        $productionApprovalRole->givePermissionTo($permissionPaymentsEdit->id);
        $productionApprovalRole->givePermissionTo($permissionApprovePayments->id);
        // $productionApprovalRole->givePermissionTo($permissionPlanDetailsAdd->id);

        $nonRetailAccountsRole = Role::findOrCreate('NON_RETAIL_ACCOUNTS', 'web');
        // Get all permissions assigned to the PRODUCTION_APPROVAL role
        $permissions = $productionApprovalRole->permissions;
        // Loop through each permission and assign it to the NON_RETAIL_ACCOUNTS role
        foreach ($permissions as $permission) {
            $nonRetailAccountsRole->givePermissionTo($permission->id);
        }
    }
}
