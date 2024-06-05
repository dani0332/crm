<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSageApiLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sage_api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->morphs('section');
            $table->unsignedSmallInteger('step');
            $table->unsignedSmallInteger('total_steps');
            $table->string('sage_end_point');
            $table->json('sage_payload');
            $table->json('response');
            $table->string('status');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
            $table->timestamps();

            // Define indexes for optimization
            $table->index('user_id');
            $table->index('status');
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
