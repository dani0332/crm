<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RevivalConversionReportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // revival section permission

        $revivalPermissions = [PermissionsEnum::CAR_REVIVAL_QUOTE_LIST, PermissionsEnum::CAR_REVIVAL_QUOTES_EDIT, PermissionsEnum::CAR_REVIVAL_QUOTES_SHOW, PermissionsEnum::REVIVAL_CONVERSION_REPORT_VIEW];

        foreach ($revivalPermissions as $item) {

            $permissionExists = DB::table('permissions')->where('name', $item)->first();
            if ($permissionExists === null) {
                DB::table('permissions')->insert(
                    [
                        'name' => $item,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]

                );
            }

            $permissionId = Permission::where('name', $item)->first()->id;

            if ($permissionId) {

                // assign permission to admin car-arevival advisor/manager and beta user

                $carRevivalAdvisorCount = Role::where('name', RolesEnum::CarRevivalAdvisor)->count();
                if ($carRevivalAdvisorCount == 0) {
                    Role::create([
                        'name' => RolesEnum::CarRevivalAdvisor,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $carRevivalAdvisor = Role::where('name', RolesEnum::CarRevivalAdvisor)->first();
                $record = DB::table('role_has_permissions')->where('role_id', $carRevivalAdvisor->id)->where('permission_id', $permissionId)->first();
                if (empty($record)) {
                    DB::table('role_has_permissions')->insert(
                        [
                            'role_id' => $carRevivalAdvisor->id,
                            'permission_id' => $permissionId,
                        ]
                    );
                }

                $carRevivalManagerCount = Role::where('name', RolesEnum::CarRevivalManager)->count();
                if ($carRevivalManagerCount == 0) {
                    Role::create([
                        'name' => RolesEnum::CarRevivalManager,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $carRevivalManager = Role::where('name', RolesEnum::CarRevivalManager)->first();

                if (! empty($carRevivalManager)) {
                    $record = DB::table('role_has_permissions')->where('role_id', $carRevivalManager->id)->where('permission_id', $permissionId)->first();
                    if (empty($record)) {
                        DB::table('role_has_permissions')->insert(
                            [
                                'role_id' => $carRevivalManager->id,
                                'permission_id' => $permissionId,
                            ]
                        );
                    }
                }

                $adminRole = Role::where('name', RolesEnum::Admin)->first();
                if (! empty($adminRole)) {
                    $record = DB::table('role_has_permissions')->where('role_id', $adminRole->id)->where('permission_id', $permissionId)->first();
                    if (empty($record)) {
                        DB::table('role_has_permissions')->insert(
                            [
                                'role_id' => $adminRole->id,
                                'permission_id' => $permissionId,
                            ]
                        );
                    }
                }

                $betaUserRole = Role::where('name', RolesEnum::BetaUser)->first();
                if (! empty($betaUserRole)) {
                    $record = DB::table('role_has_permissions')->where('role_id', $betaUserRole->id)->where('permission_id', $permissionId)->first();
                    if (empty($record)) {
                        DB::table('role_has_permissions')->insert(
                            [
                                'role_id' => $betaUserRole->id,
                                'permission_id' => $permissionId,
                            ]
                        );
                    }
                }
            }
        }
    }
}
