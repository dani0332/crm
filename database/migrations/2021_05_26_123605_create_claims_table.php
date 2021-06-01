<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email_address');
            $table->string('phone_number');
            $table->string('insurance_company');
            $table->string('insurance_type');
            $table->string('policy_number');
            $table->text('basic_details');
            $table->text('attachment_1')->nullable();
            $table->text('attachment_2')->nullable();
            $table->text('attachment_3')->nullable();
            $table->text('attachment_4')->nullable();
            $table->string('advisor_name')->nullable();
            $table->string('ticket_number')->nullable();
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
        Schema::dropIfExists('claims');
    }
}
