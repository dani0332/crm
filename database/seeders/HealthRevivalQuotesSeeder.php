<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthRevivalQuotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // revival section permission

        $healthRevivalPermissions = [PermissionsEnum::HEALTH_REVIVAL_QUOTES_LIST, PermissionsEnum::HEALTH_REVIVAL_QUOTES_EDIT, PermissionsEnum::HEALTH_REVIVAL_QUOTES_SHOW];

        foreach ($healthRevivalPermissions as $item) {

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

                // assign permission to admin health-manager and beta user

                $healthManager = Role::where('name', RolesEnum::HealthManager)->first();

                if (! empty($healthManager)) {
                    $record = DB::table('role_has_permissions')->where('role_id', $healthManager->id)->where('permission_id', $permissionId)->first();
                    if (empty($record)) {
                        DB::table('role_has_permissions')->insert(
                            [
                                'role_id' => $healthManager->id,
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

        // dtt health initial and followup template
        $dttHealthInitialAndFollowupTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_INITIAL_AND_FOLLOWUP_TEMPLATE)->first();
        if (! $dttHealthInitialAndFollowupTemplate) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_INITIAL_AND_FOLLOWUP_TEMPLATE,
                'value' => '691',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // dtt health initial without health team type
        $dttHealthInitialWithOutHealthTeamTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_INITIAL_WITHOUT_HEALTH_TEAM)->first();
        if (! $dttHealthInitialWithOutHealthTeamTemplate) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_INITIAL_WITHOUT_HEALTH_TEAM,
                'value' => '692',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // dtt health first follow up without health team type
        $dttHealthFollowupOneWithOutHealthTeamTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_TWO_DAYS_WITHOUT_HEALTH_TEAM)->first();
        if (! $dttHealthFollowupOneWithOutHealthTeamTemplate) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_TWO_DAYS_WITHOUT_HEALTH_TEAM,
                'value' => '693',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // dtt health 2nd follow up without health team type
        $dttHealthFollowupTwoWithOutHealthTeamTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_FOUR_DAYS_WITHOUT_HEALTH_TEAM)->first();
        if (! $dttHealthFollowupTwoWithOutHealthTeamTemplate) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_FOUR_DAYS_WITHOUT_HEALTH_TEAM,
                'value' => '694',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } // dtt health 2nd follow up without health team type
        $dttHealthFollowupThreeWithOutHealthTeamTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_SIX_DAYS_WITHOUT_HEALTH_TEAM)->first();
        if (! $dttHealthFollowupThreeWithOutHealthTeamTemplate) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_AFTER_SIX_DAYS_WITHOUT_HEALTH_TEAM,
                'value' => '695',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $dttHealthReplyToProd = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_REPLY_TO)->get();
        if (count($dttHealthReplyToProd) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_REPLY_TO,
                'value' => 'buyhealth@insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
        $dttHealthFollowupFromEmail = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL)->get();
        if (count($dttHealthFollowupFromEmail) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_FOLLOWUP_FROM_EMAIL,
                'value' => 'alfred@notify.insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }

        $dttHealthEnabled = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_HEALTH_ENABLED)->first();
        if (! $dttHealthEnabled) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::DTT_HEALTH_ENABLED,
                'value' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
