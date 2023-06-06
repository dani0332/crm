<?php

namespace Database\Seeders;

use App\Enums\ModulesEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class GeneralPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $modules = [
            ModulesEnum::UPLOADED_LEADS
        ];

        $permissions = ['-list', '-show', '-create', '-edit', '-delete'];
        $allowedRenewalsPermissions = [];

        foreach ($modules as $module) {
            foreach ($permissions as $permission) {
                $allowedRenewalsPermissions[] = Permission::findOrCreate(strtolower($module).$permission, 'web')->id;
            }
        }

        // Renewals Upload Permissions
        $allowedRenewalsPermissions[] = Permission::findOrCreate('renewals-upload', 'web')->id;

        // Assign (Upload & Create / Uploaded Leads) Permissions to Marketing Operations
        $marketingOperationRole = Role::findOrCreate(RolesEnum::MarketingOperations, 'web');
        $marketingOperationRole->givePermissionTo($allowedRenewalsPermissions);


        // Renewals Update and Batches Permissions
        $allowedRenewalsPermissions[] = Permission::findOrCreate('renewals-update', 'web')->id;
        $allowedRenewalsPermissions[] = Permission::findOrCreate('batches-list', 'web')->id;

        // Assign (Update and Batches) Permissions to Renewals Manager
        $renewalsManager = Role::findOrCreate(RolesEnum::RenewalsManager, 'web');
        $renewalsManager->givePermissionTo($allowedRenewalsPermissions);

    }
}
