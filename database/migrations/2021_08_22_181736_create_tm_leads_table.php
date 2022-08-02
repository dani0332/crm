<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tm_leads', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', '50');
            $table->string('phone_number', '20');
            $table->string('email_address', '50');
            $table->date('enquiry_date');
            $table->date('allocation_date');
            $table->string('notes', '2000')->nullable();
            $table->boolean('is_deleted')->default('0');
            $table->string('cdb_id', '11')->nullable();
            $table->dateTime('next_followup_date')->nullable();
            $table->integer('no_answer_count')->nullable();
            $table->date('dob')->nullable();
            $table->string('year_of_manufacture', '50')->nullable();
            $table->decimal('car_value', $precision = 14, $scale = 2)->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('modified_by_id')->nullable();
            $table->foreign('modified_by_id')->references('id')->on('users')->onDelete('no action');
            $table->integer('car_model_id')->nullable();
            $table->foreign('car_model_id')->references('id')->on('car_model')->onDelete('no action');
            $table->integer('car_make_id')->nullable();
            $table->foreign('car_make_id')->references('id')->on('car_make')->onDelete('no action');
            $table->unsignedBigInteger('tm_insurance_types_id')->nullable();
            $table->foreign('tm_insurance_types_id')->references('id')->on('tm_insurance_types')->onDelete('no action');
            $table->unsignedBigInteger('tm_call_statuses_id')->nullable();
            $table->foreign('tm_call_statuses_id')->references('id')->on('tm_call_statuses')->onDelete('no action');
            $table->unsignedBigInteger('tm_lead_statuses_id')->nullable();
            $table->foreign('tm_lead_statuses_id')->references('id')->on('tm_lead_statuses')->onDelete('no action');
            $table->integer('nationality_id')->nullable();
            $table->foreign('nationality_id')->references('id')->on('nationality')->onDelete('no action');
            $table->integer('years_of_driving_id')->nullable();
            $table->foreign('years_of_driving_id')->references('id')->on('uae_license_held_for')->onDelete('no action');
            $table->integer('emirates_of_registration_id')->nullable();
            $table->foreign('emirates_of_registration_id')->references('id')->on('emirates')->onDelete('no action');
            $table->unsignedBigInteger('tm_upload_leads_id')->nullable();
            $table->foreign('tm_upload_leads_id')->references('id')->on('tm_upload_leads')->onDelete('no action');
            $table->integer('car_type_insurance_id')->nullable();
            $table->foreign('car_type_insurance_id')->references('id')->on('car_type_insurance')->onDelete('no action');
            $table->unsignedBigInteger('tm_lead_types_id')->nullable();
            $table->foreign('tm_lead_types_id')->references('id')->on('tm_lead_types')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tm_leads');
    }
}
