<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsForHealthQuotes extends Migration
{
    /**
     * Run the migrations .
     *
     * @return void
     */
    public function up()
    {
        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'price_starting_from')) {
                $table->decimal('price_starting_from', 14, 2)->nullable();
            }
        });

        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'min_price')) {
                $table->decimal('min_price', 14, 2)->nullable();
            }

            if (! Schema::hasColumn('teams', 'max_price')) {
                $table->decimal('max_price', 14, 2)->nullable();
            }

            if (! Schema::hasColumn('teams', 'allocation_threshold_enabled')) {
                $table->tinyInteger('allocation_threshold_enabled')->default(0);
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
