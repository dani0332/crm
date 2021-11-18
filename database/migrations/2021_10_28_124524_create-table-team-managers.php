<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTeamManagers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('team_managers', function (Blueprint $table) {
            $table->id()->autoIncrement();

            $table->unsignedBigInteger('manager_id');
            $table->index('manager_id');
            $table->foreign('manager_id')->references('id')->on('users');

            $table->unsignedBigInteger('team_id');
            $table->index('team_id');
            $table->foreign('team_id')->references('id')->on('teams');
            $table->timestamps();
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
