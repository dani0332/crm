<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsHealthQuoteRequestMemberDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request_member_details', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request_member_details', 'nationality_id')) {
                $table->integer('nationality_id')->nullable();
                $table->foreign('nationality_id')->references('id')->on('nationality');
            }
            if (! Schema::hasColumn('health_quote_request_member_details', 'emirate_of_your_visa_id')) {
                $table->integer('emirate_of_your_visa_id')->nullable();
                $table->foreign('emirate_of_your_visa_id')->references('id')->on('emirates');
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
        Schema::table('health_quote_request_member_details', function (Blueprint $table) {
            //
        });
    }
}
