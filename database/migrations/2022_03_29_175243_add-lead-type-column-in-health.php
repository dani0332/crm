<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeadTypeColumnInHealth extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'lead_type_id')) {
                $table->integer('lead_type_id')->unsigned()->nullable();
                $table->foreign('lead_type_id')->references('id')->on('health_lead_type');
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
        Schema::table('health_quote_request', function (Blueprint $table) {
            //
        });
    }
}
