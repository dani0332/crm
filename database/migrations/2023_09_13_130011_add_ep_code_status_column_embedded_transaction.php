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
            if (! Schema::hasColumn('embedded_transactions', 'ep_status_code')) {
                $table->string('ep_status_code',25)->index();
                $table->foreign('ep_status_code')->references('code')->on('lookups')->cascadeOnUpdate();
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
            if (Schema::hasColumn('embedded_transactions', 'ep_status_code')) {
                $table->dropColumn('ep_status_code');
            }
        });
    }
}
