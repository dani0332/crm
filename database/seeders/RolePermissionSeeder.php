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
        Permission::firstOrCreate([
            'name' => PermissionsEnum::TAP_BETA_ACCESS,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        try {
            // permission for upload Health Rates and Coverages
            // $uploadHealthRatesPermission = Permission::where('name', PermissionsEnum::UPLOAD_HEALTH_RATES)->first();
            // if (! $uploadHealthRatesPermission) {
            //     Permission::create([
            //         'name' => PermissionsEnum::UPLOAD_HEALTH_RATES,
            //         'guard_name' => 'web',
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ]);
            // }
            // $uploadHealthCoveragesPermission = Permission::where('name', PermissionsEnum::UPLOAD_HEALTH_COVERAGES)->first();
            // if (! $uploadHealthCoveragesPermission) {
            //     Permission::create([
            //         'name' => PermissionsEnum::UPLOAD_HEALTH_COVERAGES,
            //         'guard_name' => 'web',
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ]);
            // }
            $viewAllLeadsPermission = Permission::where('name', PermissionsEnum::VIEW_ALL_LEADS)->first();
            if (! $viewAllLeadsPermission) {
                Permission::create([
                    'name' => PermissionsEnum::VIEW_ALL_LEADS,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $viewAllReportsPermission = Permission::where('name', PermissionsEnum::VIEW_ALL_REPORTS)->first();
            if (! $viewAllReportsPermission) {
                Permission::create([
                    'name' => PermissionsEnum::VIEW_ALL_REPORTS,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $th) {
            info('RolePermission Seeder issue Error:'.$th->getMessage().' Line:'.$th->getLine());
            throw $th;
        }
        // $this->addReceiveNotificationsPermission();
        // $this->searchModulePermissions();
        $this->createBusinessIntelligenceUnitRole();
        $this->addMissingAdvisorRoles(); // Add missing advisor roles on PROD
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
}
