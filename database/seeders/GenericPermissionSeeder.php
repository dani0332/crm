<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class GenericPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $healthManagerAccess = Permission::where('name', PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)->first();
        if (! $healthManagerAccess) {
            Permission::create([
                'name' => PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS,
                'guard_name' => 'web',
            ]);
        }

        $healthQuoteAccess = Permission::where('name', PermissionsEnum::HEALTH_QUOTES_ACCESS)->first();
        if (! $healthQuoteAccess) {
            Permission::create([
                'name' => PermissionsEnum::HEALTH_QUOTES_ACCESS,
                'guard_name' => 'web',
            ]);
        }

        // Add Compliance Permission to Admin
        $role = Role::where('name', RolesEnum::Admin)->first();

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS);
        }

        if (! $role->hasPermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
            $role->givePermissionTo(PermissionsEnum::HEALTH_QUOTES_ACCESS);
        }

        $this->advisorConversionReportSeeds();
    }

    public function advisorConversionReportSeeds()
    {
        $permissionList = [
                // car conversion report permission (already existing)
            PermissionsEnum::ADVISOR_CONVERSION_REPORT_VIEW => [
                RolesEnum::CarAdvisor,
                RolesEnum::CarManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::BIKE_CONVERSION_REPORT => [
                RolesEnum::BikeAdvisor,
                RolesEnum::BikeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::HEALTH_CONVERSION_REPORT => [
                RolesEnum::HealthAdvisor,
                RolesEnum::HealthManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::TRAVEL_CONVERSION_REPORT => [
                RolesEnum::TravelAdvisor,
                RolesEnum::TravelManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::LIFE_CONVERSION_REPORT => [
                RolesEnum::LifeAdvisor,
                RolesEnum::LifeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::HOME_CONVERSION_REPORT => [
                RolesEnum::HomeAdvisor,
                RolesEnum::HomeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::PET_CONVERSION_REPORT => [
                RolesEnum::PetAdvisor,
                RolesEnum::PetManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::CYCLE_CONVERSION_REPORT => [
                RolesEnum::CycleAdvisor,
                RolesEnum::CycleManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::YACHT_CONVERSION_REPORT => [
                RolesEnum::YachtAdvisor,
                RolesEnum::YachtManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::CORPLINE_CONVERSION_REPORT => [
                RolesEnum::CorpLineAdvisor,
                RolesEnum::CorplineManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::GROUPMEDICAL_CONVERSION_REPORT => [
                RolesEnum::GMAdvisor,
                RolesEnum::GMManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
        ];

        foreach ($permissionList as $permission => $roles) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

            foreach ($roles as $roleName) {
                if (($role = Role::where('name', $roleName)->first()) && !$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
