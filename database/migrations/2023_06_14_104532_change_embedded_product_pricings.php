<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ChangeEmbeddedProductPricings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('embedded_product_pricings')) {
            Schema::rename('embedded_product_pricings', 'embedded_product_options');

            Schema::table('embedded_product_options', function ($table) {
                $table->tinyInteger('is_active')->default(1)->after('variant');
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
