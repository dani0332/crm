<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('travel_quote_request_member_details', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request_member_details', 'customer_id')) {
                $table->bigInteger('customer_id')->nullable();
                $table->foreign('customer_id')->references('id')->on('customer');
            }
            if (! Schema::hasColumn('travel_quote_request_member_details', 'code')) {
                $table->string('code')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_quote_request_member_details', function (Blueprint $table) {
            //
        });
    }
};
