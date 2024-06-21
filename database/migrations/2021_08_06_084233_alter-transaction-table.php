<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('transactions', 'customer_name')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('customer_name');
            });
        }

        if (! Schema::hasColumn('customer', 'has_reward_access')) {
            Schema::table('customer', function (Blueprint $table) {
                $table->boolean('has_reward_access')->default(false);
            });
        }

        if (! Schema::hasColumn('transactions', 'customer_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('customer_id');
                $table->bigInteger('customer_id')->nullable(true);
                $table->foreign('customer_id')->references('id')->on('customer')->onDelete('no action');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
