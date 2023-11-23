<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationCodeToHealthQuoteRequestMemberDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request_member_details', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request_member_details', 'relation_code')) {
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
        Schema::table('health_quote_request_member_details', function (Blueprint $table) {
            //
        });
    }
}
