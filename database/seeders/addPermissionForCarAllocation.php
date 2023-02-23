<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use App\Models\Permission;
use App\Models\Role;
use DB;
use Illuminate\Database\Seeder;

class addPermissionForCarAllocation extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sundayResetTime = ApplicationStorage::where('key_name', 'SATURDAY_CAP_RESET_TIME')->first();
        if ($sundayResetTime == null) {
            ApplicationStorage::insert([
                'key_name' => 'SATURDAY_CAP_RESET_TIME',
                'value' => '12:55',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $normalResetTime = ApplicationStorage::where('key_name', 'NORMAL_CAP_RESET_TIME')->first();
        if ($normalResetTime == null) {
            ApplicationStorage::insert([
                'key_name' => 'NORMAL_CAP_RESET_TIME',
                'value' => '18:20',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $carAllocationStartTime = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_START_TIME')->first();
        if ($carAllocationStartTime == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_LEAD_ALLOCATION_START_TIME',
                'value' => '08:00',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $carAllocationEndTime = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_END_TIME')->first();
        if ($carAllocationEndTime == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_LEAD_ALLOCATION_END_TIME',
                'value' => '18:15',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $carPickUpFIFO = ApplicationStorage::where('key_name', 'CAR_LEAD_PICKUP_FIFO')->first();
        if ($carPickUpFIFO == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_LEAD_PICKUP_FIFO',
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
        $carPermission = Permission::where('name', 'car-lead-allocation-dashboard')->first();
        if ($carPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'car-lead-allocation-dashboard',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $healthPermission = Permission::where('name', 'health-lead-allocation-dashboard')->first();
        if ($healthPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'health-lead-allocation-dashboard',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $rulePermission = Permission::where('name', 'rule-config-list')->first();
        if ($rulePermission == null) {
            DB::table('permissions')->insert([
                'name' => 'rule-config-list',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $quadPermission = Permission::where('name', 'quad-config-list')->first();
        if ($quadPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'quad-config-list',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $tierPermission = Permission::where('name', 'tier-config-list')->first();
        if ($tierPermission == null) {
            DB::table('permissions')->insert([
                'name' => 'tier-config-list',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $leadPoolRole = Role::where('name', 'LEAD_POOL')->first();
        if ($leadPoolRole == null) {
            DB::table('roles')->insert([
                'name' => 'LEAD_POOL',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $leadPoolRoleId = Role::where('name', 'LEAD_POOL')->first()->id;
        $carLeadAllocationPermissionId = Permission::where('name', 'car-lead-allocation-dashboard')->first()->id;
        if (count(DB::table('role_has_permissions')->where('role_id', $leadPoolRoleId)->where('permission_id', $carLeadAllocationPermissionId)->get()) == 0) {
            $ruleConfigId = Permission::where('name', 'rule-config-list')->first()->id;
            $tierConfigId = Permission::where('name', 'tier-config-list')->first()->id;
            $quadConfigId = Permission::where('name', 'quad-config-list')->first()->id;
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $carLeadAllocationPermissionId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $ruleConfigId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $tierConfigId,
                ]);
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $leadPoolRoleId,
                    'permission_id' => $quadConfigId,
                ]);
        }
    }
}
