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
        $managementReport = Permission::where('name', PermissionsEnum::MANAGEMENT_REPORT)->first();
        if (! $managementReport) {
            Permission::create([
                'name' => PermissionsEnum::MANAGEMENT_REPORT,
                'guard_name' => 'web',
            ]);
        }

        // Add Permission to Admin
        $role = Role::where('name', RolesEnum::Admin)->first();

        if (! $role->hasPermissionTo(PermissionsEnum::MANAGEMENT_REPORT)) {
            $role->givePermissionTo(PermissionsEnum::MANAGEMENT_REPORT);
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

        $instantAlfredChatLogsPermission = Permission::where('name', PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS)->first();
        if (! $instantAlfredChatLogsPermission) {
            $instantAlfredChatLogsPermission = Permission::create([
                'name' => PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS,
                'guard_name' => 'web',
            ]);
        }

        if ($instantAlfredChatLogsPermission && ! $role->hasPermissionTo(PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS)) {
            $role->givePermissionTo(PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS);
        }

        $this->generateSegmentFilterPermission();
        $this->embeddedProductSeeds();
    }

    private function generateSegmentFilterPermission()
    {
        $segmentFilterPermission = Permission::where('name', PermissionsEnum::SEGMENT_FILTER)->first();
        if (! $segmentFilterPermission) {
            Permission::create([
                'name' => PermissionsEnum::SEGMENT_FILTER,
                'guard_name' => 'web',
            ]);
        }

        $roles = Role::whereIn('name', [RolesEnum::LeadPool, RolesEnum::CarManager, RolesEnum::Admin])->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo(PermissionsEnum::SEGMENT_FILTER)) {
                $role->givePermissionTo(PermissionsEnum::SEGMENT_FILTER);
            }
        }
    }

    private function embeddedProductSeeds()
    {
        // update name of existing permission
        Permission::where(['name' => 'embedded-product-advisor'])->update(['name' => PermissionsEnum::EMBEDDED_PRODUCT_ADVISOR]);
        Permission::where(['name' => 'embedded-product-admin'])->update(['name' => PermissionsEnum::EMBEDDED_PRODUCT_ADMIN]);

        $permissionList = [
            PermissionsEnum::EMBEDDED_PRODUCT_ADVISOR => [
                RolesEnum::CarAdvisor,
                RolesEnum::Admin,
                RolesEnum::Engineering,
                RolesEnum::BetaUser,
                RolesEnum::EpAdmin,
            ],
            PermissionsEnum::EMBEDDED_PRODUCT_ADMIN => [
                RolesEnum::Admin,
                RolesEnum::Engineering,
                RolesEnum::BetaUser,
                RolesEnum::EpAdmin,
            ],
        ];

        foreach ($permissionList as $permission => $roles) {
            $dataset = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            foreach ($roles as $roleName) {
                if (($role = Role::where('name', $roleName)->first()) && ! $role->hasPermissionTo($dataset->id)) {
                    $role->givePermissionTo($dataset->id);
                }
            }
        }
    }
}
