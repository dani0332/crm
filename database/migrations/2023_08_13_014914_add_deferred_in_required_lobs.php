<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeferredInRequiredLobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('car_quote_request')) {
            Schema::table('car_quote_request', function ($table) {
                if (! Schema::hasColumn('car_quote_request', 'deferred')) {
                    $table->boolean('deferred')->default(false);
                }
                if (! Schema::hasColumn('car_quote_request', 'deferred_at')) {
                    $table->date('deferred_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('health_quote_request')) {
            Schema::table('health_quote_request', function ($table) {
                if (! Schema::hasColumn('health_quote_request', 'deferred')) {
                    $table->boolean('deferred')->default(false);
                }
                if (! Schema::hasColumn('health_quote_request', 'deferred_at')) {
                    $table->date('deferred_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('bike_quote_request')) {
            Schema::table('bike_quote_request', function ($table) {
                if (! Schema::hasColumn('bike_quote_request', 'deferred')) {
                    $table->boolean('deferred')->default(false);
                }
                if (! Schema::hasColumn('bike_quote_request', 'deferred_at')) {
                    $table->date('deferred_at')->nullable();
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
        Schema::table('required_lobs', function (Blueprint $table) {
            //
        });
    }
}
