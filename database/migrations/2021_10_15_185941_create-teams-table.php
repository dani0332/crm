<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id()->autoIncrement();

            $table->string('name');

            $table->bigInteger('lead_id')->nullable(false)->unsigned();
            $table->index('lead_id');
            $table->foreign('lead_id')->references('id')->on('users');

            $table->bigInteger('user_id')->nullable(false)->unsigned();
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users');
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
