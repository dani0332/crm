<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuoteRequestTablesForAdvisorAssignedById extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('bike_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('bike_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('business_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('business_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('car_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('car_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('health_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('health_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('home_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('home_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('travel_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('travel_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('life_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('life_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('pet_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('pet_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
            });
        }
        if (! Schema::hasColumn('yacht_quote_request_detail', 'advisor_assigned_by_id')) {
            Schema::table('yacht_quote_request_detail', function (Blueprint $table) {
                $table->date('advisor_assigned_date')->nullable(false)->default(now());
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->foreign('advisor_assigned_by_id')->references('id')->on('users')->onDelete('no action');
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
