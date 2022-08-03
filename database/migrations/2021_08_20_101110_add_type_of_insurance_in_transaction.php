<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeOfInsuranceInTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('transactions', 'type_of_insurance_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->bigInteger('type_of_insurance_id')->nullable()->unsigned();
            });
            Schema::table('transactions', function ($table) {
                $table->foreign('type_of_insurance_id')->references('id')->on('type_of_insurances')->onDelete('no action');
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
        Schema::table('transaction', function (Blueprint $table) {
            //
        });
    }
}
