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
        if (($role = Role::where('name', RolesEnum::LifeAdvisor)->first()) && ! $role->hasPermissionTo(PermissionsEnum::LifeQuotesShow)) {
            $role->givePermissionTo(PermissionsEnum::LifeQuotesShow);
        }

        if (($role = Role::where('name', RolesEnum::LifeManager)->first()) && ! $role->hasPermissionTo(PermissionsEnum::LifeQuotesShow)) {
            $role->givePermissionTo([PermissionsEnum::LifeQuotesShow]);
        }

        if (($role = Role::where('name', RolesEnum::TravelAdvisor)->first()) && ! $role->hasPermissionTo(PermissionsEnum::TravelQuotesShow)) {
            $role->givePermissionTo([PermissionsEnum::TravelQuotesShow]);
        }

        if (($role = Role::where('name', RolesEnum::TravelManager)->first()) && ! $role->hasPermissionTo(PermissionsEnum::TravelQuotesShow)) {
            $role->givePermissionTo([PermissionsEnum::TravelQuotesShow]);
        }

        if (($role = Role::where('name', RolesEnum::PetAdvisor)->first()) && ! $role->hasPermissionTo(PermissionsEnum::PetQuotesShow)) {
            $role->givePermissionTo([PermissionsEnum::PetQuotesShow]);
        }

        if (($role = Role::where('name', RolesEnum::PetManager)->first()) && ! $role->hasPermissionTo(PermissionsEnum::PetQuotesShow)) {
            $role->givePermissionTo([PermissionsEnum::PetQuotesShow]);
        }
    }
}
