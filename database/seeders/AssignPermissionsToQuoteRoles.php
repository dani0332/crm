<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssignPermissionsToQuoteRoles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(($role = Role::where('name', RolesEnum::LifeAdvisor)->first())) {
            $role->syncPermissions([PermissionsEnum::LifeQuotesShow]);
        }

        if(($role = Role::where('name', RolesEnum::LifeManager)->first())) {
            $role->syncPermissions([PermissionsEnum::LifeQuotesShow]);
        }

        if(($role = Role::where('name', RolesEnum::TravelAdvisor)->first())) {
            $role->syncPermissions([PermissionsEnum::TravelQuotesShow]);
        }

        if(($role = Role::where('name', RolesEnum::TravelManager)->first())) {
            $role->syncPermissions([PermissionsEnum::TravelQuotesShow]);
        }

        if(($role = Role::where('name', RolesEnum::PetAdvisor)->first())) {
            $role->syncPermissions([PermissionsEnum::PetQuotesShow]);
        }

        if(($role = Role::where('name', RolesEnum::PetManager)->first())) {
            $role->syncPermissions([PermissionsEnum::PetQuotesShow]);
        }
    }
}
