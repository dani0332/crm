<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentlyInusredWithColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'currently_insured_with_id')) {
                $table->integer('currently_insured_with_id')->nullable();
                $table->foreign('currently_insured_with_id')->references('id')->on('insurance_provider')->onDelete('no action');
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
        //
    }
}
