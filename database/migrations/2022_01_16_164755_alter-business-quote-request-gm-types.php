<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBusinessQuoteRequestGmTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_quote_request', function (Blueprint $table) {
            $table->bigInteger('group_medical_type_id')->nullable();
            $table->foreign('group_medical_type_id')->references('id')->on('group_medical_types');
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
