<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id()->startingValue(350000);
            $table->string('approval_code')->nullable();
            $table->bigInteger('insurance_company_id');
            $table->string('customer_name');
            $table->bigInteger('handler_id');
            $table->bigInteger('payment_mode_id');
            $table->bigInteger('status_id')->nullable();
            $table->bigInteger('reason_id')->nullable();
            $table->string('prev_approval_code')->nullable();
            $table->string('prev_transaction_date')->nullable();
            $table->string('status_modified_date')->nullable();
            $table->string('status_modified_by')->nullable();
            $table->text('risk_details');
            $table->text('comments')->nullable();
            $table->string('amount_paid');
            $table->string('created_by');
            $table->string('updated_by')->nullable();
            $table->boolean('is_cancelled')->default('0');
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
        Schema::dropIfExists('transactions');
    }
}
