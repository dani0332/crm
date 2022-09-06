<?php

use Illuminate\Database\Migrations\Migration;

class Addpaymentuserroles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $role = DB::table('roles')->where('name', 'Accounts')->first();
        if ($role === null) {
            DB::table('roles')->insert(
                [
                    'name' => 'Accounts',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }

        $permission = DB::table('permissions')->where('name', 'approve-payments')->first();
        if ($permission === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'approve-payments',
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
