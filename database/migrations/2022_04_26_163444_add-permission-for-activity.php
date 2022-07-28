<?php

use Illuminate\Database\Migrations\Migration;

class AddPermissionForActivity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $activitiesList = DB::table('permissions')->where('name', 'activities-list')->first();
        if ($activitiesList === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'activities-list',
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
