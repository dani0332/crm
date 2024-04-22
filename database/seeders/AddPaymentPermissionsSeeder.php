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
                if ($role == '_MANAGER') {
                    $role->givePermissionTo($permissionApprovePayments->id);
                }
            }
        }

        $role = Role::findOrCreate('PRODUCTION_APPROVAL', 'web');
        $role->givePermissionTo($permissionPaymentsEdit->id);
        $role->givePermissionTo($permissionApprovePayments->id);

        $role = Role::findOrCreate('NON_RETAIL_ACCOUNTS', 'web');
        $role->givePermissionTo($permissionPaymentsEdit->id);
        $role->givePermissionTo($permissionApprovePayments->id);
    }
}
