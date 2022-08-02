<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarQuotePolicy extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_quote_policy', function (Blueprint $table) {
            $table->id()->autoIncrement();

            $table->bigInteger('car_quote_id');
            $table->foreign('car_quote_id')->references('id')->on('car_quote_request');

            $table->unsignedBigInteger('transactions_id');
            $table->foreign('transactions_id')->references('id')->on('transactions');

            $table->string('quote_number', '100')->nullable();
            $table->string('policy_number', '100')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            parent::commonFields($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('car_quote_policy');
    }
}
