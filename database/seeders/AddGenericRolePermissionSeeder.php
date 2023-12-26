<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddGenericRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => [PermissionsEnum::HealthQuotesList], 'role' => RolesEnum::HealthRenewalManager],
            ['name' => [PermissionsEnum::PetQuotesList, PermissionsEnum::PetQuotesCreate, PermissionsEnum::PetQuotesEdit], 'role' => RolesEnum::PetRenewalAdvisor],
            ['name' => [PermissionsEnum::CycleQuotesList, PermissionsEnum::CycleQuotesCreate, PermissionsEnum::CycleQuotesEdit], 'role' => RolesEnum::CycleRenewalAdvisor],
            ['name' => [PermissionsEnum::YachtQuotesList, PermissionsEnum::YachtQuotesCreate, PermissionsEnum::YachtQuotesEdit], 'role' => RolesEnum::YachtRenewalAdvisor],
            ['name' => [PermissionsEnum::CycleQuotesList, PermissionsEnum::CycleQuotesCreate, PermissionsEnum::CycleQuotesShow, PermissionsEnum::CycleQuotesEdit], 'role' => RolesEnum::CycleNewBusinessAdvisor],
            ['name' => [PermissionsEnum::YachtQuotesList, PermissionsEnum::YachtQuotesCreate, PermissionsEnum::YachtQuotesShow, PermissionsEnum::YachtQuotesEdit], 'role' => RolesEnum::YachtNewBusinessAdvisor],
        ];

        foreach ($permissions as $permission) {
            $getRole = Role::firstOrCreate(['name' => $permission['role']], ['guard_name' => 'web']);

            foreach ($permission['name'] as $quotePermission) {
                $getPermission = Permission::firstOrCreate(['name' => $quotePermission], ['guard_name' => 'web']);
                if (! $getRole->hasPermissionTo($getPermission->id)) {
                    $getRole->givePermissionTo($getPermission->id);
                }
            }
        }
    }
}
