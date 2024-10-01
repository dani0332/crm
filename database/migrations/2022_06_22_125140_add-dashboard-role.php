<?php

use Illuminate\Database\Migrations\Migration;

class AddDashboardRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $role = DB::table('roles')->where('name', 'SENIOR_MANAGEMENT')->first();
        if ($role === null) {
            DB::table('roles')->insert(
                [
                    'name' => 'SENIOR_MANAGEMENT',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }

        $permission = DB::table('permissions')->where('name', 'dashboard-view')->first();
        if ($permission === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'dashboard-view',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
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
