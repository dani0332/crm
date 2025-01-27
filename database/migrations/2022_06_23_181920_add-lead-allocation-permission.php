<?php

use Illuminate\Database\Migrations\Migration;

class AddLeadAllocationPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // lead-allocation-view
        $datetime = date('Y-m-d H:i:s');
        $permission = DB::table('permissions')->where('name', 'lead-allocation-view')->first();
        if ($permission === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'lead-allocation-view',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }

        $datetime = date('Y-m-d H:i:s');
        $role = DB::table('roles')->where('name', 'LEAD_POOL')->first();
        if ($role === null) {
            DB::table('roles')->insert(
                [
                    'name' => 'LEAD_POOL',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }

        $role = DB::table('roles')->where('name', 'LEAD_POOL')->first();
        $permission = DB::table('permissions')->where('name', 'lead-allocation-view')->first();
        $rolePermission = DB::table('role_has_permissions')->where('role_id', $role->id)->where('permission_id', $permission->id)->first();
        if ($rolePermission === null) {
            DB::table('role_has_permissions')->insert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
