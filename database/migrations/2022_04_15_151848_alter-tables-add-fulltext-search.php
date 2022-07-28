<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterTablesAddFulltextSearch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE health_quote_request ADD FULLTEXT search(first_name, last_name, code, mobile_no, email)');
        DB::statement('ALTER TABLE travel_quote_request ADD FULLTEXT search(first_name, last_name, code, mobile_no, email)');
        DB::statement('ALTER TABLE home_quote_request ADD FULLTEXT search(first_name, last_name, code, mobile_no, email)');
        DB::statement('ALTER TABLE life_quote_request ADD FULLTEXT search(first_name, last_name, code, mobile_no, email)');
        DB::statement('ALTER TABLE business_quote_request ADD FULLTEXT search(company_name, first_name, last_name, code, mobile_no, email)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('health_quote_request', function ($table) {
            $table->dropIndex('search');
        });
        Schema::table('travel_quote_request', function ($table) {
            $table->dropIndex('search');
        });
        Schema::table('home_quote_request', function ($table) {
            $table->dropIndex('search');
        });
        Schema::table('life_quote_request', function ($table) {
            $table->dropIndex('search');
        });
        Schema::table('business_quote_request', function ($table) {
            $table->dropIndex('search');
        });
    }
}
