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
                RolesEnum::Admin,
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
