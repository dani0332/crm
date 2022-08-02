<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarQuoteInsuranceCoverage extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('car_quote_insurance_coverage')) {
            Schema::create('car_quote_insurance_coverage', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->date('start_date');

                $table->bigInteger('car_quote_id');
                $table->foreign('car_quote_id')->references('id')->on('car_quote_request');

                $table->unsignedBigInteger('insurance_plan_id');
                $table->foreign('insurance_plan_id')->references('id')->on('car_quote_insurance_plan');

                $table->unsignedBigInteger('insurance_company_id');
                $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');

                $table->bigInteger('vehicle_type_id');
                $table->foreign('vehicle_type_id')->references('id')->on('vehicle_type');

                $table->float('excess');
                $table->float('ancillary_excess');
                $table->float('premium_price');
                $table->string('personal_accident_benefit', '200')->nullable();
                $table->string('breakdown_recovery', '200')->nullable();
                $table->string('off_road_cover', '200')->nullable();
                $table->string('rend_a_car', '200')->nullable();
                $table->string('geographical_area', '500')->nullable();
                $table->timestamps();
                $table->softDeletes();
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
        Schema::dropIfExists('car_quote_insurance_coverage');
    }
}
