<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterTeamsTableAndUserTeams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('teams', function (Blueprint $table) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            if (Schema::hasColumn('teams', 'lead_id')) {
                $table->dropForeign('teams_lead_id_foreign');
                $table->dropColumn('lead_id');
            }
            if (Schema::hasColumn('teams', 'user_id')) {
                $table->dropForeign('teams_user_id_foreign');
                $table->dropColumn('user_id');
            }
            Schema::dropIfExists('team_managers');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });
        Schema::table('user_team', function ($table) {
            $table->bigInteger('manager_id')->nullable(true)->unsigned();
            $table->index('manager_id');
            $table->foreign('manager_id')->references('id')->on('users');
        });
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
