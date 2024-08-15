<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addPermissionsForReportsAndDashbords extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminRoleId = Role::where('name', 'ADMIN')->first()->id;
        $carManagerRoleId = Role::where('name', 'CAR_MANAGER')->first()->id;
        $carAdvisorRoleId = Role::where('name', 'CAR_ADVISOR')->first()->id;
        $leadPoolRoleId = Role::where('name', 'LEAD_POOL')->first()->id;

        $leadDistribution = Permission::where('name', 'lead-distribution-report-view')->first();
        if ($leadDistribution == null) {
            DB::table('permissions')->insert([
                'name' => 'lead-distribution-report-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $leadDistributionId = Permission::where('name', 'lead-distribution-report-view')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $leadDistributionId,
                ], [
                    'role_id' => $carManagerRoleId,
                    'permission_id' => $leadDistributionId,
                ], [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $leadDistributionId,
                ]);
        }

        $advisorConversion = Permission::where('name', 'advisor-conversion-report-view')->first();
        if ($advisorConversion == null) {
            DB::table('permissions')->insert([
                'name' => 'advisor-conversion-report-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $advisorConversionId = Permission::where('name', 'advisor-conversion-report-view')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $advisorConversionId,
                ], [
                    'role_id' => $carManagerRoleId,
                    'permission_id' => $advisorConversionId,
                ], [
                    'role_id' => $carDeputyManagerRoleId,
                    'permission_id' => $advisorConversionId,
                ], [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $advisorConversionId,
                ], [
                    'role_id' => $carAdvisorRoleId,
                    'permission_id' => $advisorConversionId,
                ]);
        }

        $advisorPerformance = Permission::where('name', 'advisor-performance-report-view')->first();
        if ($advisorPerformance == null) {
            DB::table('permissions')->insert([
                'name' => 'advisor-performance-report-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $advisorPerformanceId = Permission::where('name', 'advisor-performance-report-view')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $advisorPerformanceId,
                ], [
                    'role_id' => $carManagerRoleId,
                    'permission_id' => $advisorPerformanceId,
                ], [
                    'role_id' => $carDeputyManagerRoleId,
                    'permission_id' => $advisorPerformanceId,
                ], [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $advisorPerformanceId,
                ]);
        }

        $advisorDistribution = Permission::where('name', 'advisor-distribution-report-view')->first();
        if ($advisorDistribution == null) {
            DB::table('permissions')->insert([
                'name' => 'advisor-distribution-report-view',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $advisorDistributionId = Permission::where('name', 'advisor-distribution-report-view')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $advisorDistributionId,
                ], [
                    'role_id' => $carManagerRoleId,
                    'permission_id' => $advisorDistributionId,
                ], [
                    'role_id' => $carDeputyManagerRoleId,
                    'permission_id' => $advisorDistributionId,
                ], [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $advisorDistributionId,
                ], [
                    'role_id' => $carAdvisorRoleId,
                    'permission_id' => $advisorDistributionId,
                ]);
        }
    }
}
