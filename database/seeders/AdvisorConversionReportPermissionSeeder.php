<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Enums\PermissionsEnum;
use Illuminate\Database\Seeder;

class AdvisorConversionReportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
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
            PermissionsEnum::BUSINESS_CONVERSION_REPORT => [
                RolesEnum::BusinessAdvisor,
                RolesEnum::BusinessManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::GROUPMEDICAL_CONVERSION_REPORT => [
                RolesEnum::BusinessAdvisor,
                RolesEnum::BusinessManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
        ];

        foreach ($permissionList as $permission => $roles) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

            foreach($roles as $roleName) {
                if (($role = Role::where('name', $roleName)->first()) && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
