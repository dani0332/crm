<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
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
    }
}
