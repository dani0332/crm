<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseMigration;

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
            $table->string('quote_number','100')->nullable();
            $table->string('policy_number','100')->nullable();
            $table->date('issue_date');
            $table->date('start_date');
            $table->date('end_date');
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
