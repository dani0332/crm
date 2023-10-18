<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEpCodeStatusColumnEmbeddedTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('embedded_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('embedded_transactions', 'embedded_transaction_status_code')) {
                $table->string('embedded_transaction_status_code', 25);
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
            if (Schema::hasColumn('embedded_transactions', 'embedded_transaction_status_code')) {
                $table->dropColumn('embedded_transaction_status_code');
            }
        });
    }
}
