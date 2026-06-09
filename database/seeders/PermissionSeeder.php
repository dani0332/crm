<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private const WEB_GUARD = 'web';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => PermissionsEnum::VIEW_PCP,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::NONRULE_LEADALLOCATION,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::DELETE_ADDITIONAL_CONTACT,
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->addBuyLeadsAdminPermission();
        $this->addTransAppSearchPermission();
        $this->addReTriggerPolicyAutomationDevicePermission();
        $this->addEpDocumentManualOverridePermission();
        $this->addConversionOptimizationEngineReportPermission();
        $this->addComplianceDocumentUploadPermission();
        $this->seedEditPlanAfterTransactionApprovalPermission();
        $this->addLifeRevivalPermissions();
        $this->addHomeRevivalPermissions();
    }

    private function addEpDocumentManualOverridePermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::EP_DOCUMENT_MANUAL_OVERRIDE,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        if ($roles) {
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    LoggerService::info("Permission {$permission->name} assigned to role {$role->name}");
                } else {
                    LoggerService::info("Role {$role->name} already has permission {$permission->name}");
                }
            }
        }
    }

    private function addBuyLeadsAdminPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::BUY_LEADS_ADMIN,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        if ($roles) {
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    info("Permission {$permission->name} assigned to role {$role->name}");
                } else {
                    info("Role {$role->name} already has permission {$permission->name}");
                }
            }
        }
    }

    private function addTransAppSearchPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::TRANSAPP_SEARCH,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where(function ($query) {
                $query->whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])
                    ->orWhereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->whereIn('name', [PermissionsEnum::TransAppCreate, PermissionsEnum::TransAppEdit, PermissionsEnum::TransAppDelete]);
                    });
            })
            ->get();

        if ($roles->isNotEmpty()) {
            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    LoggerService::info("Permission {$permission->name} assigned to role {$role->name}");
                } else {
                    LoggerService::info("Role {$role->name} already has permission {$permission->name}");
                }
            }
        }
    }

    /**
     * IMCRM: device policy issuance document re-trigger. Runs before DeviceQuoteSeeder; device quote roles
     * may not exist yet, so DeviceQuoteSeeder must assign this permission when it creates those roles.
     */
    private function addReTriggerPolicyAutomationDevicePermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::RE_TRIGGER_POLICY_AUTOMATION_DEVICE,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                RolesEnum::Admin,
                RolesEnum::Engineering,

                RolesEnum::SmartPhoneAdvisor,
                RolesEnum::SmartPhoneManager,

            ])
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                LoggerService::info("Permission {$permission->name} assigned to role {$role->name}");
            }
        }
    }

    private function addConversionOptimizationEngineReportPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::CONVERSION_OPTIMIZATION_ENGINE_REPORT_VIEW,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roles = Role::whereIn('name', [RolesEnum::Admin, RolesEnum::Engineering])->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
                LoggerService::info("Permission {$permission->name} assigned to role {$role->name}");
            } else {
                LoggerService::info("Role {$role->name} already has permission {$permission->name}");
            }
        }
    }

    private function addComplianceDocumentUploadPermission(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => PermissionsEnum::COMPLIANCE_DOCUMENT_UPLOAD,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', RolesEnum::ComplianceSuperUser)
            ->first();

        if ($role === null) {
            LoggerService::info('COMPLIANCE_SUPER_USER role not found; skipping compliance-document-upload assignment');

            return;
        }

        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
            LoggerService::info("Permission {$permission->name} assigned to role {$role->name}");
        } else {
            LoggerService::info("Role {$role->name} already has permission {$permission->name}");
        }
    }
    private function seedEditPlanAfterTransactionApprovalPermission(): void
    {
        Permission::firstOrCreate(
            [
                'name' => PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL,
                'guard_name' => self::WEB_GUARD,
            ],
        );
    }

    private function addLifeRevivalPermissions(): void
    {
        $permissions = [
            [
                'name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_LIST,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_SHOW,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::LIFE_REVIVAL_QUOTES_EDIT,
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => $permissionData['guard_name'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function addHomeRevivalPermissions(): void
    {
        $permissions = [
            [
                'name' => PermissionsEnum::HOME_REVIVAL_QUOTES_LIST,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::HOME_REVIVAL_QUOTES_SHOW,
                'guard_name' => 'web',
            ],
            [
                'name' => PermissionsEnum::HOME_REVIVAL_QUOTES_EDIT,
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                [
                    'name' => $permissionData['name'],
                    'guard_name' => $permissionData['guard_name'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
