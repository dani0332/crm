<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInsuranceTypeToCarPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_plan', function (Blueprint $table) {
            if (! Schema::hasColumn('car_plan', 'insurance_type')) {
                $table->string('insurance_type', 200)->nullable();
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
        Schema::table('car_plan', function (Blueprint $table) {
            if (Schema::hasColumn('car_plan', 'insurance_type')) {
                $table->dropColumn('insurance_type');
            }
        });
    }
}
