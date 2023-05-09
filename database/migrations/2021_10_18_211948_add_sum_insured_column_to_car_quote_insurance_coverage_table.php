<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSumInsuredColumnToCarQuoteInsuranceCoverageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_insurance_coverage', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_insurance_coverage', 'sum_insured')) {
                $table->string('sum_insured')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_quote_insurance_coverage', function (Blueprint $table) {
            if (Schema::hasColumn('car_quote_insurance_coverage', 'sum_insured')) {
                $table->dropColumn('sum_insured');
            }
        });
    }
}
