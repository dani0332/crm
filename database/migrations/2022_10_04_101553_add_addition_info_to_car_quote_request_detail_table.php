<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionInfoToCarQuoteRequestDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request_detail', 'lost_approval_status') && ! Schema::hasColumn('car_quote_request_detail', 'lost_approval_reason')) {
                $table->string('lost_approval_status', 30)->nullable();
                $table->string('lost_approval_reason')->nullable();
            }
        });
    }
}
