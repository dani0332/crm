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

        // Book policy permissions & roles
        $bookPolicyPermissions = [
            ['name' => PermissionsEnum::BOOK_POLICY_EDIT, 'role' => RolesEnum::PRODUCTION],
            ['name' => PermissionsEnum::BOOK_POLICY_EDIT, 'role' => RolesEnum::NRA],
            ['name' => PermissionsEnum::BOOK_POLICY_EDIT, 'role' => RolesEnum::FINANCE],
        ];

        foreach ($bookPolicyPermissions as $permission) {
            $permissionRecord = Permission::where('name', $permission['name'])->first();

            if (! $permissionRecord) {
                $permissionRecord = Permission::create([
                    'name' => $permission['name'],
                    'guard_name' => 'web',
                ]);
            }

            if (! empty($permission['role'])) {
                $role = Role::where('name', $permission['role'])->first();

                if (! $role) {
                    $role = Role::create([
                        'name' => $permission['role'],
                        'guard_name' => 'web',
                    ]);
                }

                if (! $role->hasPermissionTo($permissionRecord->id)) {
                    $role->givePermissionTo($permissionRecord->id);
                }
            }
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
        $this->syncMasterPermissionList();
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
        Permission::where(['name' => 'embedded-product-view'])->update(['name' => PermissionsEnum::EMBEDDED_PRODUCT_ADVISOR]);

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
            $dataset = Permission::findOrCreate($permission, 'web');
            foreach ($roles as $roleName) {
                if (($role = Role::where('name', $roleName)->first()) && ! $role->hasPermissionTo($dataset->id)) {
                    $role->givePermissionTo($dataset->id);
                }
            }
        }
    }

    private function syncMasterPermissionList()
    {
        $permissionList = [
            PermissionsEnum::ADD_PROFORMA_PAYMENT_REQUEST_DROPDOWN_OPTION => [
                RolesEnum::Admin,
                RolesEnum::ServiceExecutive,
                RolesEnum::SeniorManagement,
                RolesEnum::GMManager,
                RolesEnum::CorplineManager,
            ],
            PermissionsEnum::ENABLE_PROFORMA_PDF_DOWNLOAD_BUTTON => [
            ],
            PermissionsEnum::POLICY_DETAILS_ADD => [
                RolesEnum::Admin,
                RolesEnum::Production,
                RolesEnum::NRA,
                RolesEnum::OperationExecutive,
                RolesEnum::SeniorManagement,
            ],
            PermissionsEnum::BOOK_POLICY_DETAILS_ADD => [
                RolesEnum::Admin,
                RolesEnum::Production,
                RolesEnum::NRA,
                RolesEnum::OperationExecutive,
                RolesEnum::SeniorManagement,
            ],
            PermissionsEnum::SEND_POLICY_TO_CUSTOMER_BUTTON => [
                RolesEnum::Admin,
                RolesEnum::Production,
                RolesEnum::NRA,
                RolesEnum::OperationExecutive,
                RolesEnum::SeniorManagement,
            ],
            PermissionsEnum::SEND_AND_BOOK_POLICY_BUTTON => [
                RolesEnum::Admin,
                RolesEnum::Production,
                RolesEnum::NRA,
                RolesEnum::OperationExecutive,
                RolesEnum::SeniorManagement,
            ],
            PermissionsEnum::BOOK_POLICY_BUTTON => [
                RolesEnum::Admin,
                RolesEnum::Production,
                RolesEnum::NRA,
                RolesEnum::OperationExecutive,
                RolesEnum::SeniorManagement,
            ],
        ];

        $this->syncPermissionsWithRole($permissionList);

    }

    private function syncPermissionsWithRole($permissionList)
    {
        foreach ($permissionList as $permission => $roles) {
            $dataset = Permission::findOrCreate($permission, 'web');
            foreach ($roles as $roleName) {
                if (($role = Role::findOrCreate($roleName, 'web')) && ! $role->hasPermissionTo($dataset->id)) {
                    $role->givePermissionTo($dataset->id);
                }
            }
        }
    }
}
