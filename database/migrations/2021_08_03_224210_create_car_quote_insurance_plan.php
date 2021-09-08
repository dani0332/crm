<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseMigration;

class CreateCarQuoteInsurancePlan extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('car_quote_insurance_plan')) {
            Schema::create('car_quote_insurance_plan', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->string('code');
                $table->string('text');
                $table->string('text_ar');
                $table->softDeletes();
                $table->timestamps();
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
        Schema::dropIfExists('car_quote_insurance_plan');
    }
}
