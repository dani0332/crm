<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAdditionalInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('customer_additional_info')) {
            Schema::create('customer_additional_info', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('customer_id')->nullable();
                $table->string('quote_type', '20');
                $table->integer('quote_request_id');
                $table->string('email_address', '150')->nullable();
                $table->string('mobile_no', '20')->nullable();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('customer')->onDelete('no action');
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
        Schema::dropIfExists('customer_additional_info');
    }
}
