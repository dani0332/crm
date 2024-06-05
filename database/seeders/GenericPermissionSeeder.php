<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class GenericPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Conversion as at report Permissions
        $conversionReportPermissions = [
            PermissionsEnum::CONVERSION_AS_AT_REPORT,
            PermissionsEnum::MOTOR_AS_AT_REPORT_MANAGER,
            PermissionsEnum::HEALTH_AS_AT_REPORT_MANAGER,
            PermissionsEnum::TRAVEL_AS_AT_REPORT_MANAGER,
            PermissionsEnum::LIFE_AS_AT_REPORT_MANAGER,
            PermissionsEnum::HOME_AS_AT_REPORT_MANAGER,
            PermissionsEnum::PET_AS_AT_REPORT_MANAGER,
            PermissionsEnum::CYCLE_AS_AT_REPORT_MANAGER,
            PermissionsEnum::YACHT_AS_AT_REPORT_MANAGER,
            PermissionsEnum::BUSINESS_AS_AT_REPORT_MANAGER,
            PermissionsEnum::GROUPMEDICALS_AS_AT_REPORT_MANAGER,
            PermissionsEnum::ACCESS_REPORT_SM,
        ];

        foreach ($conversionReportPermissions as $conversionPermission) {
            $permission = Permission::where('name', $conversionPermission)->first();
            if (! $permission) {
                Permission::create([
                    'name' => $conversionPermission,
                    'guard_name' => 'web',
                ]);
            }

            // Add Compliance Permission to Admin
            $role = Role::where('name', RolesEnum::Admin)->first();

            if (! $role->hasPermissionTo($conversionPermission)) {
                $role->givePermissionTo($conversionPermission);
            }
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

        $permissions = [
            PermissionsEnum::SAVE_QUOTE_NOTES,
            PermissionsEnum::UPDATE_QUOTE_NOTES,
            PermissionsEnum::DELETE_QUOTE_NOTES,
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();

            if (! $permission) {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

        $rolesForManageQuote = [
            RolesEnum::Admin,
            RolesEnum::HealthManager,
            RolesEnum::HealthAdvisor,
            RolesEnum::HomeManager,
            RolesEnum::HomeAdvisor,
            RolesEnum::PetManager,
            RolesEnum::PetAdvisor,
            RolesEnum::CycleManager,
            RolesEnum::CycleAdvisor,
            RolesEnum::YachtManager,
            RolesEnum::YachtAdvisor,
            RolesEnum::CorplineManager,
            RolesEnum::CorpLineAdvisor,
        ];

        foreach ($rolesForManageQuote as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');

            foreach ($permissions as $permissionName) {
                $role->givePermissionTo($permissionName);
            }
        }

        $this->generateSegmentFilterPermission();
        $this->embeddedProductSeeds();
        $this->advisorConversionReportSeeds();
        $this->quoteSyncSeeds();
        $this->advisorDistributionReportSeeds();
        $this->addMotorHeadNewRole();
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

    private function quoteSyncSeeds()
    {
        $permissionList = [
            PermissionsEnum::QUOTE_SYNC_LOGS => [],
        ];

        foreach ($permissionList as $permission => $roles) {
            $dataset = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }

    private function embeddedProductSeeds()
    {
        // update name of existing permission
        Permission::where(['name' => 'embedded-product-advisor'])->update(['name' => PermissionsEnum::EMBEDDED_PRODUCT_VIEW]);
        Permission::where(['name' => 'embedded-product-admin'])->update(['name' => PermissionsEnum::EMBEDDED_PRODUCT_PAYMENT_CANCEL]);

        $permissionList = [
            PermissionsEnum::EMBEDDED_PRODUCT_VIEW => [
                RolesEnum::CarAdvisor,
                RolesEnum::Admin,
                RolesEnum::Engineering,
                RolesEnum::BetaUser,
                RolesEnum::EpAdmin,
            ],
            PermissionsEnum::EMBEDDED_PRODUCT_PAYMENT_CANCEL => [
                RolesEnum::Admin,
                RolesEnum::Engineering,
                RolesEnum::BetaUser,
                RolesEnum::EpAdmin,
            ],
            PermissionsEnum::EMBEDDED_PRODUCT_CONFIG => [
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
                RolesEnum::RMAdvisor,
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
                if (($role = Role::where('name', $roleName)->first()) && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $this->addComprehensiveConversionDashboardPermissions();
    }

    public function addComprehensiveConversionDashboardPermissions()
    {
        $permissionList = [
            PermissionsEnum::COMPREHENSIVE_DASHBOARD_VIEW => [
                RolesEnum::CarManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::BIKE_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::BikeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::HEALTH_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::HealthManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::TRAVEL_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::TravelManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::LIFE_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::LifeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::HOME_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::HomeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::PET_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::PetManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::CYCLE_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::CycleManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::YACHT_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::YachtManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::CORPLINE_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::CorplineManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::GROUPMEDICAL_COMPREHENSIVE_DASHBOARD => [
                RolesEnum::GMManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
        ];

        foreach ($permissionList as $permission => $roles) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);

            foreach ($roles as $roleName) {
                if (($role = Role::where('name', $roleName)->first()) && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function advisorDistributionReportSeeds()
    {
        $permissionList = [
            // car distribution report permission (already existing)
            PermissionsEnum::ADVISOR_DISTRIBUTION_REPORT_VIEW => [
                RolesEnum::CarAdvisor,
                RolesEnum::CarManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::BIKE_DISTRIBUTION_REPORT => [
                RolesEnum::BikeAdvisor,
                RolesEnum::BikeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::HEALTH_DISTRIBUTION_REPORT => [
                RolesEnum::RMAdvisor,
                RolesEnum::HealthManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::TRAVEL_DISTRIBUTION_REPORT => [
                RolesEnum::TravelAdvisor,
                RolesEnum::TravelManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::LIFE_DISTRIBUTION_REPORT => [
                RolesEnum::LifeAdvisor,
                RolesEnum::LifeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::HOME_DISTRIBUTION_REPORT => [
                RolesEnum::HomeAdvisor,
                RolesEnum::HomeManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::PET_DISTRIBUTION_REPORT => [
                RolesEnum::PetAdvisor,
                RolesEnum::PetManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::CYCLE_DISTRIBUTION_REPORT => [
                RolesEnum::CycleAdvisor,
                RolesEnum::CycleManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::YACHT_DISTRIBUTION_REPORT => [
                RolesEnum::YachtAdvisor,
                RolesEnum::YachtManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::CORPLINE_DISTRIBUTION_REPORT => [
                RolesEnum::CorpLineAdvisor,
                RolesEnum::CorplineManager,
                RolesEnum::SeniorManagement,
                RolesEnum::Admin,
                RolesEnum::Engineering,
            ],
            PermissionsEnum::GROUPMEDICAL_DISTRIBUTION_REPORT => [
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
                if (($role = Role::where('name', $roleName)->first()) && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function addMotorHeadNewRole()
    {
        $carManagerRole = Role::findByName('CAR_MANAGER');

        if (! $carManagerRole) {
            Log::warning('CAR_MANAGER role not found. Motor Head role creation skipped.');

            return;
        }

        $motorHeadRole = Role::firstOrCreate([
            'name' => 'MOTOR_HEAD',
        ], [
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $motorHeadRole->syncPermissions($carManagerRole->permissions);
        } catch (\Exception $e) {
            Log::error('Error assigning permissions to Motor Head role: '.$e->getMessage());
        }
    }
}
