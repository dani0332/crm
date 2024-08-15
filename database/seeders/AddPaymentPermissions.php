<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPaymentPermissions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $newRole = Role::findOrCreate(RolesEnum::NonCCPaymentVerifier, 'web');
        // Missing Role NON_CC_PAYMENT_VERIFIER
        $allRoles = [
            RolesEnum::Admin,
            RolesEnum::PA,
            RolesEnum::NRA,
            RolesEnum::OperationExecutive,
            RolesEnum::ServiceExecutive,
            RolesEnum::SeniorManagement,
            RolesEnum::CarAdvisor,
            RolesEnum::CarManager,
            RolesEnum::HealthManager,
            RolesEnum::HealthAdvisor,
            RolesEnum::RMAdvisor,
            RolesEnum::TravelManager,
            RolesEnum::TravelAdvisor,
            RolesEnum::LifeManager,
            RolesEnum::LifeAdvisor,
            RolesEnum::HomeManager,
            RolesEnum::HomeAdvisor,
            RolesEnum::PetAdvisor,
            RolesEnum::PetManager,
            RolesEnum::BikeAdvisor,
            RolesEnum::BikeManager,
            RolesEnum::CycleManager,
            RolesEnum::CycleAdvisor,
            RolesEnum::YachtAdvisor,
            RolesEnum::YachtManager,
            RolesEnum::GMAdvisor,
            RolesEnum::GMManager,
            RolesEnum::CorpLineAdvisor,
            RolesEnum::CorplineManager,
            RolesEnum::NonCCPaymentVerifier,
        ];
        foreach ($allRoles as $role) {

            $adminRole = Role::where('name', $role)->first();
            $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_DISCOUNT_EDIT, 'web');
            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $permission->id,
                    ]
                );
            }

            $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_DISCOUNT_ADD, 'web');

            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $permission->id,
                    ]
                );
            }

            $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_CREDIT_APPROVAL_ADD, 'web');
            $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
            if ($rolePermission === null) {
                DB::table('role_has_permissions')->insert(
                    [
                        'role_id' => $adminRole->id,
                        'permission_id' => $permission->id,
                    ]
                );
            }

            if (! in_array($role, [RolesEnum::ServiceExecutive, RolesEnum::HealthManager, RolesEnum::RMAdvisor, RolesEnum::LifeManager,
                RolesEnum::LifeAdvisor, RolesEnum::GMAdvisor, RolesEnum::GMManager])) {
                $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_FREQUENCY_UPRONT_SPLIT_COLLECTED_BY_BROKER_ADD, 'web');
                $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
                if ($rolePermission === null) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $adminRole->id,
                            'permission_id' => $permission->id,
                        ]
                    );
                }
            }

            if (in_array($role, [RolesEnum::Admin, RolesEnum::PA, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::SeniorManagement, RolesEnum::CorpLineAdvisor,
                RolesEnum::CorplineManager, RolesEnum::NonCCPaymentVerifier])) {
                $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_BROKER_ADD, 'web');
                $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
                if ($rolePermission === null) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $adminRole->id,
                            'permission_id' => $permission->id,
                        ]
                    );
                }
            }

            if (in_array($role, [RolesEnum::Admin, RolesEnum::PA, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::SeniorManagement, RolesEnum::CorpLineAdvisor,
                RolesEnum::CorplineManager, RolesEnum::ServiceExecutive, RolesEnum::GMAdvisor, RolesEnum::GMManager, RolesEnum::HealthManager, RolesEnum::HealthAdvisor, RolesEnum::NonCCPaymentVerifier])) {
                $permission = Permission::findOrCreate(PermissionsEnum::PAYMENTS_FREQUENCY_TERMS_COLLECTED_BY_INSURER_ADD, 'web');
                $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
                if ($rolePermission === null) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $adminRole->id,
                            'permission_id' => $permission->id,
                        ]
                    );
                }
            }

            if (in_array($role, [RolesEnum::NonCCPaymentVerifier, RolesEnum::Admin, RolesEnum::PA, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::SeniorManagement,
            ])) {

                $permission = Permission::findOrCreate(PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_BROKER, 'web');
                $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
                if ($rolePermission === null) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $adminRole->id,
                            'permission_id' => $permission->id,
                        ]
                    );
                }
            }

            if (in_array($role, [RolesEnum::Admin, RolesEnum::PA, RolesEnum::NRA, RolesEnum::OperationExecutive, RolesEnum::SeniorManagement,
            ])) {
                $permission = Permission::findOrCreate(PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_INSURER, 'web');
                $rolePermission = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permission->id)->first();
                if ($rolePermission === null) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $adminRole->id,
                            'permission_id' => $permission->id,
                        ]
                    );
                }
            }
        }
    }
}
