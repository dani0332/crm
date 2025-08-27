<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $this->addReceiveNotificationsPermission();
        // $this->searchModulePermissions();
        // $this->createBusinessIntelligenceUnitRole();
        // $this->addMissingAdvisorRoles(); // Add missing advisor roles on PROD
        $this->addRetryPrePaymentPermission();
        $this->addVoidPaymentEmbeddedPermission(); // add EP permissions
        // $this->paymentsVoid();
        // $this->addBridgerSkipPermission();
        $this->addBridgerSkipPermission();
        $this->addPaymentVerificationLowerAmountPermission();
        $this->addPostPrepaymentButtonPermission();
        $this->addInsurerPaymentLinkPermission();
        $this->sendUpdateCancelPermission();
        $this->addRenewalsUploadPermission();
        // $this->addPolicyDetailsAddVatPermission();
        $this->addNationalityAllocationConfigPermission();
        $this->addPlanDetailsEditPermission();
        $this->addOverrideCommissionPermission();
        $this->addAssignClientSupportPermission();
        $this->addSupportSpecialistRoles();
        $this->addLeadsByEmailPermission();
        $this->addEmbeddedProductPaymentCancelAdminPermission();

        $this->addExportHomePuaUpdatesPermission();
        $this->addClaimsPermissions(); // Add claims permissions
    }

    private function addReceiveNotificationsPermission()
    {
        $roles = Role::whereIn('name', [RolesEnum::CarAdvisor, RolesEnum::TravelAdvisor, RolesEnum::HealthAdvisor, RolesEnum::PetAdvisor, RolesEnum::BikeAdvisor, RolesEnum::HomeAdvisor, RolesEnum::LifeAdvisor, RolesEnum::CycleAdvisor, RolesEnum::YachtAdvisor, RolesEnum::JetskiAdvisor, RolesEnum::BusinessAdvisor, RolesEnum::CorpLineAdvisor])->get();
        $receiveNotificationsPermission = Permission::firstOrCreate([
            'name' => PermissionsEnum::RECEIVE_NOTIFICATIONS ?? 'receive-notifications',
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($roles as $role) {

            if (! $role->hasPermissionTo($receiveNotificationsPermission)) {
                $role->givePermissionTo($receiveNotificationsPermission);
                info("Permission {$receiveNotificationsPermission->name} assigned to role {$role->name}");
            } else {
                info("Role {$role->name} already has permission {$receiveNotificationsPermission->name}");
            }
        }
    }

    private function searchModulePermissions(): void
    {
        // Add Search across all LOBs permission
        $searchAcrossLOBsPermissions = [PermissionsEnum::SEARCH_ALL_LEAD_LOB, PermissionsEnum::DATA_EXTRACTION_SEARCH_ALL_LEADS];

        foreach ($searchAcrossLOBsPermissions as $searchAcrossLOBsPermission) {
            $permission = Permission::where('name', $searchAcrossLOBsPermission)->first();

            if (! $permission) {
                Permission::create([
                    'name' => $searchAcrossLOBsPermission,
                    'guard_name' => 'web',
                ]);
                $role = Role::where('name', RolesEnum::Admin)->first();

                if (! $role->hasPermissionTo($searchAcrossLOBsPermission)) {
                    $role->givePermissionTo($searchAcrossLOBsPermission);
                }
            }
        }
    }

    private function createBusinessIntelligenceUnitRole(): void
    {
        $roleBIU = Role::firstOrCreate([
            'name' => RolesEnum::BusinessIntelligenceUnit,
            'guard_name' => 'web',
        ]);

        $accountAndFinanceRoles = Role::whereIn('name', [RolesEnum::Accounts, RolesEnum::FINANCE])->get();

        $permissionsFromAccountAndFinanceRoles = $accountAndFinanceRoles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');

        $additionalPermissions = collect([
            Permission::firstOrCreate([
                'name' => 'view-all-leads',
                'guard_name' => 'web',
            ]),
            Permission::firstOrCreate([
                'name' => 'view-all-reports',
                'guard_name' => 'web',
            ]),
        ]);

        $allPermissions = $permissionsFromAccountAndFinanceRoles->merge($additionalPermissions)->unique('id');

        $roleBIU->syncPermissions($allPermissions);
    }

    private function addMissingAdvisorRoles(): void
    {
        $missingAdvisorRoles = [RolesEnum::CarNewBusinessAdvisor, RolesEnum::LifeRenewalAdvisor];
        foreach ($missingAdvisorRoles as $missingAdvisorRole) {
            Role::firstOrCreate([
                'name' => $missingAdvisorRole,
                'guard_name' => 'web',
            ]);
        }
    }

    private function addVoidPaymentEmbeddedPermission(): void
    {
        $roles = Role::whereIn('name', [RolesEnum::EpAdmin, RolesEnum::Admin, RolesEnum::Engineering])->get();
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::EMBEDDED_PRODUCT_PAYMENT_VOID,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    private function paymentsVoid(): void
    {
        $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_VOID, 'web');
        $role = Role::where('name', RolesEnum::Engineering)->first();
        if ($role && ! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    private function addBridgerSkipPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::SKIP_BRIDGER_AML,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addPaymentVerificationLowerAmountPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::PAYMENT_VERIFICATION_LOWER_AMOUNT,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addInsurerPaymentLinkPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::INSURER_PAYMENT_LINK,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addPostPrepaymentButtonPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::CAN_POST_PREMIUM_PREPAYMENT,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addLeadAllocationLobPermissions()
    {
        $permissions = [
            PermissionsEnum::HOME_LEADPOOL,
            PermissionsEnum::LIFE_LEADPOOL,
            PermissionsEnum::YACHT_LEADPOOL,
            PermissionsEnum::PET_LEADPOOL,
            PermissionsEnum::CYCLE_LEADPOOL,
            PermissionsEnum::CORPLINE_LEADPOOL,
            PermissionsEnum::GROUP_MEDICAL_LEADPOOL,
            PermissionsEnum::SAVINGS_LEADPOOL,
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function sendUpdateCancelPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::CANCEL_SEND_UPDATE,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addPolicyDetailsAddVatPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::POLICY_DETAILS_ADD_VAT,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addRenewalsUploadPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::RENEWAL_UPLOAD_NONMOTOR,
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate([
            'name' => PermissionsEnum::RENEWALS_BATCHES_NONMOTOR,
            'guard_name' => 'web',
        ]);
    }

    private function addNationalityAllocationConfigPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::NATIONALITY_ALLOCATION_CONFIG,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addRetryPrePaymentPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::RETRY_PREPAYMENT_BUTTON,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addPlanDetailsEditPermission(): void
    {
        $permission = Permission::firstOrCreate(
            [
                'name' => PermissionsEnum::PLAN_DETAILS_EDIT,
                'guard_name' => 'web',
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $adminRole = Role::where('name', RolesEnum::Admin)->first();

        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }
    }

    private function addOverrideCommissionPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::OVERRIDE_COMMISSION_LIMIT,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addClaimsPermissions(): void
    {
        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        $claimsPermissions = PermissionsEnum::getClaimsPermissions();

        foreach ($claimsPermissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    info("Permission {$permission->name} assigned to {$role->name} role");
                } else {
                    info("{$role->name} role already has permission {$permission->name}");
                }
            }

        }
    }

    private function addAssignClientSupportPermission(): void
    {
        // Create ASSIGN_CLIENT_SUPPORT permission
        $clientSupportPermission = Permission::firstOrCreate(
            [
                'name' => PermissionsEnum::ASSIGN_CLIENT_SUPPORT,
                'guard_name' => 'web',
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create ASSIGN_LEAD_ADVISOR permission
        $leadAdvisorPermission = Permission::firstOrCreate(
            [
                'name' => PermissionsEnum::ASSIGN_LEAD_ADVISOR,
                'guard_name' => 'web',
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $adminRole = Role::where('name', RolesEnum::Admin)->first();

        if ($adminRole) {
            // Assign ASSIGN_CLIENT_SUPPORT permission to Admin role
            if (! $adminRole->hasPermissionTo($clientSupportPermission)) {
                $adminRole->givePermissionTo($clientSupportPermission);
            }

            // Assign ASSIGN_LEAD_ADVISOR permission to Admin role
            if (! $adminRole->hasPermissionTo($leadAdvisorPermission)) {
                $adminRole->givePermissionTo($leadAdvisorPermission);
            }
        }
    }

    private function addSupportSpecialistRoles(): void
    {
        $supportRoles = [
            RolesEnum::CLIENTSUPPORT,
            RolesEnum::CLIENTSUPPORTLEAD,
        ];

        foreach ($supportRoles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addLeadsByEmailPermission(): void
    {
        Permission::firstOrCreate([
            'name' => PermissionsEnum::LEADS_BY_EMAIL,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addEmbeddedProductPaymentCancelAdminPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::EMBEDDED_PRODUCT_MANUAL_OVERRIDE,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $engineeringRole = Role::where('name', RolesEnum::Engineering)->first();

        if ($engineeringRole && ! $engineeringRole->hasPermissionTo($permission)) {
            $engineeringRole->givePermissionTo($permission);
        }
    }

    private function addExportHomePuaUpdatesPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::EXPORT_HOME_PUA_UPDATES,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
