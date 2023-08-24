<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddErrorEmailSendField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('health_quote_request')) {
            Schema::table('health_quote_request', function ($table) {
                if (! Schema::hasColumn('health_quote_request', 'is_error_email_sent')) {
                    $table->boolean('is_error_email_sent')->nullable()->default(false);
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
