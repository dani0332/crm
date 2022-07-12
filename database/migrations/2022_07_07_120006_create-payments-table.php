<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->float('captured_amount', 16,2);
                $table->string('code', 15);
                $table->dateTime('captured_at');
                $table->bigInteger('quote_id');
                $table->string('payment_reference',1000);
                $table->integer('quote_type_id');
                $table->foreign('quote_type_id')->references('id')->on('quote_type')->onDelete('no action');
                $table->integer('payment_status_id');
                $table->foreign('payment_status_id')->references('id')->on('payment_status')->onDelete('no action');
                $table->integer('provider_id');
                $table->foreign('provider_id')->references('id')->on('insurance_provider')->onDelete('no action');
                $table->string('payment_method_code');
                $table->foreign('payment_method_code')->references('code')->on('payment_methods')->onDelete('no action');
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
        //
    }
}
