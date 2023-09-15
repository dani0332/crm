<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirstNameAndRelationCodeToTravelQuoteRequestMemberDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('travel_quote_request_member_details', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request_member_details', 'first_name')) {
                $table->string('first_name')->nullable();
            }

            if (! Schema::hasColumn('travel_quote_request_member_details', 'nationality_id')) {
                $table->integer('nationality_id')->nullable();
                $table->foreign('nationality_id')->references('id')->on('nationality');
            }

            if (! Schema::hasColumn('travel_quote_request_member_details', 'relation_code')) {
                $table->string('relation_code')->nullable();
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
        Schema::table('travel_quote_request_member_details', function (Blueprint $table) {
            //
        });
    }
}
