<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviousQuotePolicyPremiumToBusinessQuoteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('business_quote_request', 'previous_quote_policy_premium')) {
                $table->decimal('previous_quote_policy_premium')->nullable();
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
        Schema::table('business_quote_request', function (Blueprint $table) {
            //
        });
    }
}
