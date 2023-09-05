<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CommercialMakeModelKeywordsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions[] = Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS_CREATE, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS_SHOW, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS_STORE, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS_EDIT, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::COMMERCIAL_KEYWORDS_UPDATE, 'web');

        $permissions[] = Permission::findOrCreate(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES_CREATE, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES_SHOW, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES_EDIT, 'web');
        $permissions[] = Permission::findOrCreate(PermissionsEnum::CONFIGURE_COMMERCIAL_VEHICLES_STORE, 'web');

        $adminRole = Role::findOrCreate(RolesEnum::Admin, 'web');
        foreach ($permissions as $permission) {
            if (! $adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }
        }

    }
}
