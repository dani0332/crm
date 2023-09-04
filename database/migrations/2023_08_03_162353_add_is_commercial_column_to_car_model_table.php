<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCommercialColumnToCarModelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_model', function (Blueprint $table) {
            if (! Schema::hasColumn('car_model', 'is_commercial')) {
                $table->boolean('is_commercial')
                    ->default(false)
                    ->after('is_active');
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
        Schema::table('car_model', function (Blueprint $table) {
            if (Schema::hasColumn('car_model', 'is_commercial')) {
                $table->dropColumn('is_commercial');
            }
        });
    }
}
