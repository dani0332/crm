<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerAdditionalContactTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('customer_additional_contact')) {
            Schema::create('customer_additional_contact', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('customer_id')->nullable();
                $table->string('key', '255')->nullable();
                $table->string('value', '255')->nullable();
                $table->timestamps();
                $table->index(['customer_id', 'key']);
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
        Schema::dropIfExists('customer_additional_contact');
    }
}
