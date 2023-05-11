<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEmbeddedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('embedded_products', function (Blueprint $table) {
            if (! Schema::hasColumn('embedded_products', 'pricing_type')) {
                $table->string('pricing_type', '255')->nullable()->after('company_documents');
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
        Schema::table('embedded_products', function (Blueprint $table) {
            if (Schema::hasColumn('embedded_products', 'pricing_type')) {
                $table->dropColumn('pricing_type');
            }
        });
    }
}
