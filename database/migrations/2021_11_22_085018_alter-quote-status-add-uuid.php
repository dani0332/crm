<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuoteStatusAddUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('quote_status', 'uuid')) {
            Schema::table('quote_status', function (Blueprint $table) {
                $table->uuid('uuid')->default(DB::raw('(UUID())'));
            });
        }
        if (! Schema::hasColumn('quote_status', 'created_by')) {
            Schema::table('quote_status', function (Blueprint $table) {
                $table->string('created_by');
            });
        }
        if (! Schema::hasColumn('quote_status', 'updated_by')) {
            Schema::table('quote_status', function (Blueprint $table) {
                $table->string('updated_by');
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
