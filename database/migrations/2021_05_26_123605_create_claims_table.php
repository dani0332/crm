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
            $table->string('first_name','255');
            $table->string('last_name','255');
            $table->string('email_address','150');
            $table->string('phone_number','20');
            $table->string('insurance_company','255');
            $table->string('policy_number','150');
            $table->string('additional_notes','2000');
            $table->integer('ticket_number','10')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('assigned_to_id','20')->nullable();
            $table->unsignedBigInteger('created_by_id','20')->nullable();
            $table->unsignedBigInteger('modified_by_id','20')->nullable();
            $table->unsignedBigInteger('typeofinsurance_id','20')->nullable();
            $table->unsignedBigInteger('subtypeofinsurance_id','20')->nullable();
            $table->unsignedBigInteger('claimsstatus_id','20')->nullable();
            $table->unsignedBigInteger('carrepaircoverage_id','20')->nullable();
            $table->unsignedBigInteger('carrepairtype_id','20')->nullable();
            $table->unsignedBigInteger('rentacar_id','20')->nullable();
            $table->integer('car_make_id','11')->nullable();
            $table->integer('car_model_id','11')->nullable();
            $table->string('plate_number','20')->nullable();
            $table->string('insurer_reference','255')->nullable();
            $table->string('standard_excess_payable','255')->nullable();
            $table->string('liability','255')->nullable();
            $table->string('workshop','255')->nullable();
            $table->date('date_of_loss')->nullable();
            $table->decimal('claim_amount', $precision = 14, $scale = 2)->nullable();
            $table->tinyInteger('is_deleted')->default('0');

            $table->foreign('typeofinsurance_id')->references('id')->on('type_of_insurances')->onDelete('no action');
            $table->foreign('subtypeofinsurance_id')->references('id')->on('sub_type_of_insurances')->onDelete('no action');
            $table->foreign('claimsstatus_id')->references('id')->on('claims_statuses')->onDelete('no action');
            $table->foreign('carrepaircoverage_id')->references('id')->on('car_repair_coverages')->onDelete('no action');
            $table->foreign('carrepairtype_id')->references('id')->on('car_repair_types')->onDelete('no action');
            $table->foreign('rentacar_id')->references('id')->on('rent_a_cars')->onDelete('no action');
            $table->foreign('car_make_id')->references('id')->on('car_make')->onDelete('no action');
            $table->foreign('car_model_id')->references('id')->on('car_model')->onDelete('no action');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('modified_by_id')->references('id')->on('users')->onDelete('no action');
            $table->foreign('assigned_to_id')->references('id')->on('users')->onDelete('no action');
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
