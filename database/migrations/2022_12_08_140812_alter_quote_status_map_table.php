<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterQuoteStatusMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('quote_status_map')) {
            Schema::table('quote_status_map', function (Blueprint $table) {
                if (Schema::hasColumn('quote_status_map', 'type_of_insurance')) {
                    $table->dropColumn('type_of_insurance');
                }
                if (! Schema::hasColumn('quote_status_map', 'quote_type_id')) {
                    $table->integer('quote_type_id')->nullable()->after('id');
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
