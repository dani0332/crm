<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaIdColumnToBikeQuoteRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('bike_quote_request')) {
            Schema::table('bike_quote_request', function (Blueprint $table) {
                if (! Schema::hasColumn('bike_quote_request', 'pa_id')) {
                    $table->unsignedBigInteger('pa_id')->nullable();
                    $table->foreign('pa_id')->references('id')->on('users')->onDelete('no action');
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
        if (Schema::hasTable('bike_quote_request')) {
            Schema::table('bike_quote_request', function (Blueprint $table) {
                if (Schema::hasColumn('bike_quote_request', 'pa_id')) {
                    $table->dropColumn('pa_id');
                }
            });
        }
    }
}
