<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddColumnInTrackEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('track_emails')) {
            Schema::table('track_emails', function ($table) {
                if (! Schema::hasColumn('track_emails', 'is_ce_processed')) {
                    $table->boolean('is_ce_processed')->nullable()->default(false);
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
        if (Schema::hasTable('track_emails')) {
            Schema::table('track_emails', function ($table) {
                if (Schema::hasColumn('track_emails', 'is_ce_processed')) {
                    $table->dropColumn('is_ce_processed');
                }
            });
        }
    }
}
