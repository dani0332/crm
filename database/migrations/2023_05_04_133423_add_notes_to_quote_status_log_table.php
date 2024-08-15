<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotesToQuoteStatusLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_status_log', function (Blueprint $table) {

            if (! Schema::hasColumn('quote_status_log', 'notes')) {
                $table->text('notes')->nullable()->default(null);
            }

            if (! Schema::hasColumn('quote_status_log', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->default(null);
                $table->foreign('created_by')->references('id')->on('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
