<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnEmbeddedTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('embedded_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('embedded_transactions', 'is_selected')) {
                $table->boolean('is_selected')->nullable()->default(false);
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
        Schema::table('embedded_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('embedded_transactions', 'is_selected')) {
                $table->dropColumn('is_selected');
            }
        });
    }
}
