<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnAdvisorIdForBusinessQuoteRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_quote_request', function (Blueprint $table) {
            if (Schema::hasColumn('business_quote_request', 'advisor_id')) {
                $table->unsignedBigInteger('advisor_id')->nullable()->change();
                $table->foreign('advisor_id')->references('id')->on('users')->onDelete('no action');
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
