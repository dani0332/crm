<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInsuranceCompanyIdToInsuranceProviderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_provider', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_provider', 'insurance_company_id')) {
                $table->bigInteger('insurance_company_id')->nullable()->unsigned();
                $table->index('insurance_company_id');
                $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
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
        Schema::table('insurance_provider', function (Blueprint $table) {
            //
        });
    }
}
