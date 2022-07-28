<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUserTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_team', function (Blueprint $table) {
            if (! Schema::hasColumn('user_team', 'created_at')) {
                $table->dateTime('created_at')->default(date('Y-m-d H:i:s'));
            }
            if (! Schema::hasColumn('user_team', 'updated_at')) {
                $table->dateTime('updated_at')->default(date('Y-m-d H:i:s'));
            }
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
