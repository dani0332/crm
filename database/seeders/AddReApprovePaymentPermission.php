<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\PermissionsEnum;
use App\Models\Permission;

class AddReApprovePaymentPermission extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionPaymentsCreate = Permission::findOrCreate(PermissionsEnum::ReApprovePayments, 'web');
    }
}
