<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentDuplicateQuoteIdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('business_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
            }
        });
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
            }
        });
        Schema::table('home_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('home_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
            }
        });
        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
            }
        });
        Schema::table('life_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('life_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
            }
        });
        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
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
