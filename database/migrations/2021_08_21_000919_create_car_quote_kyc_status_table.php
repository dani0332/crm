<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarQuoteKycStatusTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('car_quote_kyc_status')) {
            Schema::create('car_quote_kyc_status', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->bigInteger('car_quote_id');
                $table->foreign('car_quote_id')->references('id')->on('car_quote_request');
                $table->smallInteger('status')->default(0);
                $table->string('notes', 5000)->nullable();
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
        Schema::dropIfExists('car_quote_kyc_status');
    }
}
