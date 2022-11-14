<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewalStatusProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renewal_status_processes', function (Blueprint $table) {
            $table->id();
            $table->string('batch', 50);
            $table->unsignedInteger('total_leads');
            $table->unsignedInteger('total_completed')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->string('status', 100);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
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
        Schema::dropIfExists('renewal_status_processes');
    }
}
