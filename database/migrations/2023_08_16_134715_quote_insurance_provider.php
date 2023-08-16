<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuoteInsuranceProvider extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('insurer_quote_type_mapping');

        if (! Schema::hasTable('quote_insurance_provider')) {
            Schema::create('quote_insurance_provider', function (Blueprint $table) {
                $table->integer('quote_type_id');
                $table->integer('insurance_provider_id');
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
        //
    }
}
