<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddGenericRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => [PermissionsEnum::HealthQuotesList], 'role' => [RolesEnum::HealthRenewalManager, RolesEnum::Admin]],
            ['name' => [PermissionsEnum::PetQuotesList, PermissionsEnum::PetQuotesCreate, PermissionsEnum::PetQuotesEdit], 'role' => [RolesEnum::PetRenewalAdvisor, RolesEnum::Admin]],
            ['name' => [PermissionsEnum::CycleQuotesList, PermissionsEnum::CycleQuotesCreate, PermissionsEnum::CycleQuotesEdit], 'role' => [RolesEnum::CycleRenewalAdvisor, RolesEnum::Admin]],
            ['name' => [PermissionsEnum::YachtQuotesList, PermissionsEnum::YachtQuotesCreate, PermissionsEnum::YachtQuotesEdit], 'role' => [RolesEnum::YachtRenewalAdvisor, RolesEnum::Admin]],
            ['name' => [PermissionsEnum::CycleQuotesList, PermissionsEnum::CycleQuotesCreate, PermissionsEnum::CycleQuotesShow, PermissionsEnum::CycleQuotesEdit], 'role' => [RolesEnum::CycleNewBusinessAdvisor, RolesEnum::Admin]],
            ['name' => [PermissionsEnum::YachtQuotesList, PermissionsEnum::YachtQuotesCreate, PermissionsEnum::YachtQuotesShow, PermissionsEnum::YachtQuotesEdit], 'role' => [RolesEnum::YachtNewBusinessAdvisor, RolesEnum::Admin]],
            ['name' => [PermissionsEnum::LEAD_CARD_SEARCH], 'role' => [
                RolesEnum::HealthAdvisor, RolesEnum::HealthManager, RolesEnum::Admin,
                RolesEnum::HomeAdvisor, RolesEnum::HomeManager,
                RolesEnum::CorpLineAdvisor, RolesEnum::CorplineManager,
                RolesEnum::PetAdvisor, RolesEnum::PetManager,
                RolesEnum::CycleAdvisor, RolesEnum::CycleManager,
                RolesEnum::YachtAdvisor, RolesEnum::YachtManager,
            ]],
            ['name' => [PermissionsEnum::STALE_LEADS_REPORT], 'role' => [RolesEnum::Admin]],
            ['name' => [PermissionsEnum::PIPELINE_REPORT], 'role' => [RolesEnum::Admin]],
            ['name' => [PermissionsEnum::HEALTH_CARD_VIEW], 'role' => [
                RolesEnum::HealthManager, RolesEnum::HealthAdvisor,
                RolesEnum::RMAdvisor, RolesEnum::EBPAdvisor, RolesEnum::Admin,
            ]],
            ['name' => [PermissionsEnum::CORPLINE_CARD_VIEW], 'role' => [
                RolesEnum::CorplineManager, RolesEnum::CorpLineAdvisor, RolesEnum::Admin,
            ]],
            ['name' => [PermissionsEnum::HOME_CARD_VIEW], 'role' => [
                RolesEnum::HomeManager, RolesEnum::HomeAdvisor, RolesEnum::Admin,
            ]],
            ['name' => [PermissionsEnum::CYCLE_CARD_VIEW], 'role' => [
                RolesEnum::CycleManager, RolesEnum::CycleAdvisor, RolesEnum::Admin,
            ]],
            ['name' => [PermissionsEnum::PET_CARD_VIEW], 'role' => [
                RolesEnum::PetManager, RolesEnum::PetAdvisor, RolesEnum::Admin,
            ]],
            ['name' => [PermissionsEnum::YACHT_CARD_VIEW], 'role' => [
                RolesEnum::YachtManager, RolesEnum::YachtAdvisor, RolesEnum::Admin,
            ]],
            ['name' => [PermissionsEnum::DOWNLOAD_ALL_DOCUMENTS], 'role' => [
                RolesEnum::HealthAdvisor, RolesEnum::HealthManager,
                RolesEnum::HomeAdvisor, RolesEnum::HomeManager,
                RolesEnum::CorpLineAdvisor, RolesEnum::CorplineManager,
                RolesEnum::PetAdvisor, RolesEnum::PetManager,
                RolesEnum::CycleAdvisor, RolesEnum::CycleManager,
                RolesEnum::YachtAdvisor, RolesEnum::YachtManager,
                RolesEnum::TravelAdvisor, RolesEnum::TravelManager,
                RolesEnum::CarAdvisor, RolesEnum::CarManager,
                RolesEnum::LifeAdvisor, RolesEnum::LifeManager,
                RolesEnum::JetskiAdvisor, RolesEnum::JetskiManager,
                RolesEnum::BikeAdvisor, RolesEnum::BikeManager,
            ]],
        ];

        foreach ($permissions as $permission) {
            foreach ($permission['name'] as $quotePermission) {
                $getPermission = Permission::firstOrCreate(['name' => $quotePermission], ['guard_name' => 'web']);
                foreach ($permission['role'] as $role) {
                    $getRole = Role::firstOrCreate(['name' => $role], ['guard_name' => 'web']);
                    if (! $getRole->hasPermissionTo($getPermission->id)) {
                        $getRole->givePermissionTo($getPermission->id);
                    }
                }
            }
        }
    }
}
