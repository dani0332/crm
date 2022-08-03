<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarQuotePaymentTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('car_quote_payment')) {
            Schema::create('car_quote_payment', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->bigInteger('car_quote_id');
                $table->foreign('car_quote_id')->references('id')->on('car_quote_request');
                $table->string('mode', 100)->nullable();
                $table->string('method', 5000)->nullable();
                $table->string('comment', 2000)->nullable();
                parent::commonFields($table);
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
        Schema::dropIfExists('car_quote_payment');
    }
}
