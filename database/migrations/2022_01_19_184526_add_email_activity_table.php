<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('email_activity')) {
            Schema::create('email_activity', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->longText('api_response');
                $table->boolean('successful');
                $table->string('email', '255');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_activity');
    }
}
