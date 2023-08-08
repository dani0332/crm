<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRuleMotorCorporatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rule_motor_corporates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')
                ->constrained('rules')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('car_make_id')->nullable();
            $table->foreign('car_make_id')
                ->references('id')
                ->on('car_make')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->integer('car_model_id');
            $table->foreign('car_model_id')
                ->references('id')
                ->on('car_model')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

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
        Schema::dropIfExists('rule_motor_corporates');
    }
}
