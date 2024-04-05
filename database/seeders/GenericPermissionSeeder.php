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

        // Plans Selection & Plan Details Section Permissions
        $planDetailsAdd = Permission::where('name', PermissionsEnum::PLAN_DETAILS_ADD)->first();
        if (! $planDetailsAdd) {
            Permission::create([
                'name' => PermissionsEnum::PLAN_DETAILS_ADD,
                'guard_name' => 'web',
            ]);

            $rolesForPlanDetails = [
                RolesEnum::Admin,
                RolesEnum::Production,
                RolesEnum::PA,
                RolesEnum::ServiceExecutive,
                RolesEnum::SeniorManagement,
                RolesEnum::LifeManager,
                RolesEnum::HomeManager,
                RolesEnum::PetManager,
                RolesEnum::BikeManager,
                RolesEnum::BikeAdvisor,
                RolesEnum::CycleManager,
                RolesEnum::CycleAdvisor,
                RolesEnum::YachtManager,
                RolesEnum::YachtAdvisor,
                RolesEnum::GMManager,
                RolesEnum::GMAdvisor,
                RolesEnum::CorplineManager,
                RolesEnum::CorpLineAdvisor,
            ];

            foreach ($rolesForPlanDetails as $roleForPlanDetails) {
                $roleForPlanDetails = Role::findOrCreate($roleForPlanDetails, 'web');
                $roleForPlanDetails->givePermissionTo(PermissionsEnum::PLAN_DETAILS_ADD);
            }
        }

        $availablePlanSelect = Permission::where('name', PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON)->first();
        if (! $availablePlanSelect) {
            Permission::create([
                'name' => PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON,
                'guard_name' => 'web',
            ]);

            $rolesForAvailablePlanSelect = [
                RolesEnum::Admin,
                RolesEnum::SeniorManagement,
                RolesEnum::CarManager,
                RolesEnum::CarAdvisor,
                RolesEnum::HealthManager,
                RolesEnum::RMAdvisor,
                RolesEnum::TravelManager,
                RolesEnum::TravelAdvisor,

            ];

            foreach ($rolesForAvailablePlanSelect as $roleForAvailablePlanSelect) {
                $roleForAvailablePlanSelect = Role::findOrCreate($roleForAvailablePlanSelect, 'web');
                $roleForAvailablePlanSelect->givePermissionTo(PermissionsEnum::AVAILABLE_PLANS_SELECT_BUTTON);
            }
            
        }
    }
}
