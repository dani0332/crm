<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeEmbeddedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('embedded_products')) {
            Schema::table('embedded_products', function (Blueprint $table) {
                $table->text('description')->change();
                $table->renameColumn('description2', 'logic_description');
                $table->renameColumn('email_template_id', 'email_template_ids');
                $table->string('product_category')->after('product_type');
                $table->bigInteger('product_validity')->nullable()->after('product_category');
                $table->text('uncheck_message')->after('pricing_type');
                $table->tinyInteger('is_active')->default(1)->after('uncheck_message');
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
        if (Schema::hasTable('embedded_products')) {
            Schema::table('embedded_products', function (Blueprint $table) {
                $table->string('description')->change();
                $table->renameColumn('logic_description', 'description2');
                $table->dropColumn('uncheck_message');
                $table->dropColumn('is_active');
            });
        }
    }
}
