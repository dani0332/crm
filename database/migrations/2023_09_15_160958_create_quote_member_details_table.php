<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteMemberDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_member_details', function (Blueprint $table) {
            $table->id();
            $table->integer('quote_type_id');
            $table->foreign('quote_type_id')->references('id')->on('quote_type');
            $table->integer('quote_request_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->dateTime('dob')->nullable();
            $table->integer('nationality_id')->nullable();
            $table->foreign('nationality_id')->references('id')->on('nationality');
            $table->string('relation_code')->nullable();
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
        Schema::dropIfExists('quote_member_details');
    }
}
