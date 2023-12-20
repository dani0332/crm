<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RenewalsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions[] = Permission::findOrCreate(PermissionsEnum::RenewalsUpload, 'web')->id;
        $permissions[] = Permission::findOrCreate(PermissionsEnum::RenewalsUploadedLeadList, 'web')->id;
        $permissions[] = Permission::findOrCreate(PermissionsEnum::EXPORT_NO_CONTACTINFO, 'web')->id;

        $marketingOperationRole = Role::findOrCreate(RolesEnum::MarketingOperations, 'web');
        foreach ($permissions as $permission) {
            if (! $marketingOperationRole->hasPermissionTo($permission)) {
                $marketingOperationRole->givePermissionTo($permission);
            }
        }

        $permissions[] = Permission::findOrCreate(PermissionsEnum::RenewalsUploadUpdate, 'web')->id;
        $permissions[] = Permission::findOrCreate(PermissionsEnum::RenewalsBatches, 'web')->id;

        $renewalsManagerRole = Role::findOrCreate(RolesEnum::RenewalsManager, 'web');
        foreach ($permissions as $permission) {
            if (! $renewalsManagerRole->hasPermissionTo($permission)) {
                $renewalsManagerRole->givePermissionTo($permission);
            }
        }

        Permission::findOrCreate(PermissionsEnum::RENEWAL_BATCHES_CREATE, 'web')->id;
        Permission::findOrCreate(PermissionsEnum::RENEWAL_BATCHES_LIST, 'web')->id;
        Permission::findOrCreate(PermissionsEnum::RENEWAL_BATCHES_EDIT, 'web')->id;
        Permission::findOrCreate(PermissionsEnum::RENEWAL_BATCH_REPORT, 'web')->id;
    }
}
