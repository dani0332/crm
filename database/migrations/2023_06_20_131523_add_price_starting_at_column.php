<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddPriceStartingAtColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('health_quote_request')) {
            Schema::table('health_quote_request', function ($table) {
                if (! Schema::hasColumn('health_quote_request', 'price_starting_from')) {
                    $table->decimal('price_starting_from', 14, 2)->nullable();
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
        //
    }
}
