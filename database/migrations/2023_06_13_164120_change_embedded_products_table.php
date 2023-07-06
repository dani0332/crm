<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
                if (! Schema::hasColumn('embedded_products', 'product_category')) {
                    $table->string('product_category');
                }

                if (! Schema::hasColumn('embedded_products', 'product_validity')) {
                    $table->bigInteger('product_validity')->nullable();
                }

                if (! Schema::hasColumn('embedded_products', 'uncheck_message')) {
                    $table->text('uncheck_message')->nullable();
                }

                if (! Schema::hasColumn('embedded_products', 'is_active')) {
                    $table->tinyInteger('is_active')->default(1);
                }

                if (Schema::hasColumn('embedded_products', 'description') && DB::getSchemaBuilder()->getColumnType('embedded_products', 'description') == \App\Enums\GenericRequestEnum::TypeString) {
                    $table->text('description')->change();
                }

                if (Schema::hasColumn('embedded_products', 'description2')) {
                    $table->renameColumn('description2', 'logic_description');
                }

                if (Schema::hasColumn('embedded_products', 'email_template_id')) {
                    $table->renameColumn('email_template_id', 'email_template_ids');
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
