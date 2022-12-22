<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableHealthQuoteRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('health_quote_request')) {
            Schema::table('health_quote_request', function (Blueprint $table) {
                if (! Schema::hasColumn('health_quote_request', 'sponsor_category_id')) {
                    $table->unsignedBigInteger('sponsor_category_id')->nullable();
                    $table->foreign('sponsor_category_id')->references('id')->on('sponsor_category')->onDelete('no action');
                }
                if (! Schema::hasColumn('health_quote_request', 'health_plan_type_id')) {
                    $table->unsignedBigInteger('health_plan_type_id')->nullable();
                    $table->foreign('health_plan_type_id')->references('id')->on('health_plan_type')->onDelete('no action');
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
