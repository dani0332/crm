<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialRoleForLeadAllocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $role = DB::table('roles')->where('name', 'SUPER_MANAGER_LEAD_ALLOCATION')->first();
        if ($role === null) {
            DB::table('roles')->insert(
                array(
                    'name' => 'SUPER_MANAGER_LEAD_ALLOCATION',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
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
