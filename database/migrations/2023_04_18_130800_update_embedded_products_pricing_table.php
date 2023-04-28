<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmbeddedProductsPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('embedded_product_pricings', function (Blueprint $table) {
            if (! Schema::hasColumn('embedded_product_pricings', 'variant')) {
                $table->string('variant', '255')->nullable()->after('price');
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
        Schema::table('embedded_product_pricings', function (Blueprint $table) {
            if (Schema::hasColumn('embedded_product_pricings', 'variant')) {
                $table->dropColumn('variant');
            }
        });
    }
}
