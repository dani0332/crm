<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            //permission for upload Health Rates and Coverages
            $uploadHealthRatesPermission = Permission::where('name', PermissionsEnum::UPLOAD_HEALTH_RATES)->first();
            if (! $uploadHealthRatesPermission) {
                Permission::create([
                    'name' => PermissionsEnum::UPLOAD_HEALTH_RATES,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $uploadHealthCoveragesPermission = Permission::where('name', PermissionsEnum::UPLOAD_HEALTH_COVERAGES)->first();
            if (! $uploadHealthCoveragesPermission) {
                Permission::create([
                    'name' => PermissionsEnum::UPLOAD_HEALTH_COVERAGES,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $bookingFailedEditPermission = Permission::where('name', PermissionsEnum::BOOKING_FAILED_EDIT)->first();
            if (! $bookingFailedEditPermission) {
                Permission::create([
                    'name' => PermissionsEnum::BOOKING_FAILED_EDIT,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $quoteRawData = Permission::where('name', PermissionsEnum::QUOTE_RAW_DATA)->first();
            if (! $quoteRawData) {
                Permission::create([
                    'name' => PermissionsEnum::QUOTE_RAW_DATA,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $role = Role::where('name', RolesEnum::Engineering)->first();

            if (! $role->hasPermissionTo($quoteRawData)) {
                $role->givePermissionTo($quoteRawData);
            }

            $roles = Role::whereIn('name', [RolesEnum::Admin])->get();
            $permission = Permission::firstOrCreate([
                'name' => PermissionsEnum::SIC_HEALTH_CONFIG ?? 'sic-health-config',
                'guard_name' => 'web',
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // Update permissions for each role
            foreach ($roles as $role) {
                // Check if the role already has the permission
                $record = DB::table('role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->first();

                // If the permission is not assigned to the role, insert it
                if (empty($record)) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }

            $paymentSummaryPermission = Permission::where('name', PermissionsEnum::MANAGER_AUTHORISED_PAYMENT_SUMMARY)->first();
            if (! $paymentSummaryPermission) {
                Permission::create([
                    'name' => PermissionsEnum::MANAGER_AUTHORISED_PAYMENT_SUMMARY,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $migrateInslyLead = Permission::where('name', PermissionsEnum::MIGRATE_INSLY_LEAD)->first();
            if (! $migrateInslyLead) {
                Permission::create([
                    'name' => PermissionsEnum::MIGRATE_INSLY_LEAD,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $th) {
            info('RolePermission Seeder issue Error:'.$th->getMessage().' Line:'.$th->getLine());
            throw $th;
        }
        $this->addTravelSicAllocationPermission();
    }

    public function addTravelSicAllocationPermission()
    {
        try {
            $travelSicAllocationPermission = Permission::firstOrCreate(
                ['name' => PermissionsEnum::TRAVEL_SIC_ALLOCATION],
                ['guard_name' => 'web']
            );

            if ($travelSicAllocationPermission->wasRecentlyCreated) {
                Log::info('Permission created: '.PermissionsEnum::TRAVEL_SIC_ALLOCATION);
            } else {
                Log::info('Permission already exists: '.PermissionsEnum::TRAVEL_SIC_ALLOCATION);
            }

            $roles = [RolesEnum::TravelManager, RolesEnum::LeadPool];

            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();

                if (! $role) {
                    Log::warning("Role not found: {$roleName}");

                    continue;
                }

                if (! $role->hasPermissionTo($travelSicAllocationPermission)) {
                    $role->givePermissionTo($travelSicAllocationPermission);
                    Log::info("Permission {$travelSicAllocationPermission->name} assigned to role {$roleName}");
                } else {
                    Log::info("Role {$roleName} already has permission {$travelSicAllocationPermission->name}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Error while assigning permission: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
