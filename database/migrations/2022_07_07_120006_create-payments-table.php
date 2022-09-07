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
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->float('captured_amount', 16, 2);
                $table->string('code', 15);
                $table->unsignedBigInteger('paymentable_id')->nullable();
                $table->string('paymentable_type', 255)->nullable();
                $table->string('reference', 1000);
                $table->integer('payment_status_id');
                $table->integer('insurance_provider_id');
                $table->integer('plan_id');
                $table->string('payment_methods_code');
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('updated_by');
                $table->boolean('is_approved')->default(false);
                $table->dateTime('authorized_at')->nullable();
                $table->dateTime('captured_at')->nullable();
                $table->string('collection_type', 255);
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
        Schema::dropIfExists('payments');
    }
}
