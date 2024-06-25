<?php

use Illuminate\Database\Migrations\Migration;

class AddDashboardPermissionToRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $role = DB::table('roles')->where('name', 'SENIOR_MANAGEMENT')->first();
        $permission = DB::table('permissions')->where('name', 'dashboard-view')->first();
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
