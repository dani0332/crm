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
            $table->bigIncrements('id');
            $table->string('first_name', '255');
            $table->string('last_name', '255');
            $table->string('email_address', '150');
            $table->string('phone_number', '20');
            $table->string('insurance_company', '255');
            $table->string('policy_number', '150');
            $table->string('additional_notes', '2000');
            $table->integer('ticket_number')->nullable();

            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('no action');

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');

            $table->unsignedBigInteger('modified_by_id')->nullable();
            $table->foreign('modified_by_id')->references('id')->on('users')->onDelete('no action');

            $table->unsignedBigInteger('type_of_insurances_id')->nullable();
            $table->foreign('type_of_insurances_id')->references('id')->on('type_of_insurances')->onDelete('no action');

            $table->unsignedBigInteger('sub_type_of_insurance_id')->nullable();
            $table->foreign('sub_type_of_insurance_id')->references('id')->on('business_type_of_insurance')->onDelete('no action');

            $table->unsignedBigInteger('claims_status_id')->nullable();
            $table->foreign('claims_status_id')->references('id')->on('claims_statuses')->onDelete('no action');

            $table->unsignedBigInteger('car_repair_coverage_id')->nullable();
            $table->foreign('car_repair_coverage_id')->references('id')->on('car_repair_coverages')->onDelete('no action');

            $table->unsignedBigInteger('car_repair_type_id')->nullable();
            $table->foreign('car_repair_type_id')->references('id')->on('car_repair_types')->onDelete('no action');

            $table->unsignedBigInteger('rent_a_car_id')->nullable();
            $table->foreign('rent_a_car_id')->references('id')->on('rent_a_cars')->onDelete('no action');

            $table->integer('car_make_id')->nullable();
            $table->foreign('car_make_id')->references('id')->on('car_make')->onDelete('no action');

            $table->integer('car_model_id')->nullable();
            $table->foreign('car_model_id')->references('id')->on('car_model')->onDelete('no action');

            $table->string('plate_number', '20')->nullable();
            $table->string('insurer_reference', '255')->nullable();
            $table->string('standard_excess_payable', '255')->nullable();
            $table->string('liability', '255')->nullable();
            $table->string('workshop', '255')->nullable();
            $table->date('date_of_loss')->nullable();
            $table->decimal('claim_amount', $precision = 14, $scale = 2)->nullable();
            $table->boolean('is_deleted')->default('0');
            $table->string('attachment_1', '255')->nullable();
            $table->string('attachment_2', '255')->nullable();
            $table->string('attachment_3', '255')->nullable();
            $table->string('attachment_4', '255')->nullable();
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
