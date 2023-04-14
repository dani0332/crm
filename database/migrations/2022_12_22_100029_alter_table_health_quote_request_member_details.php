<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableHealthQuoteRequestMemberDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('health_quote_request_member_details')) {
            Schema::table('health_quote_request_member_details', function (Blueprint $table) {
                if (Schema::hasColumn('health_quote_request_member_details', 'emirates_id')) {
                    $table->renameColumn('emirates_id', 'emirate_of_your_visa_id');
                }
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
