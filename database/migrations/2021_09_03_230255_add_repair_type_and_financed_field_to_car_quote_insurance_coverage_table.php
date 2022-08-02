<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRepairTypeAndFinancedFieldToCarQuoteInsuranceCoverageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_insurance_coverage', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_insurance_coverage', 'repair_type')) {
                $table->string('repair_type')->nullable();
            }
            if (! Schema::hasColumn('car_quote_insurance_coverage', 'financed_by')) {
                $table->string('financed_by')->nullable();
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
            //
        });
    }
}
