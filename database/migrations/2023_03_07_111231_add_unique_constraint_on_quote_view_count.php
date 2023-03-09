<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintOnQuoteViewCount extends Migration
{
    public function up()
    {
        Schema::table('quote_view_count', function (Blueprint $table) {
            // Add a unique constraint on quote_id and user_id
            $table->unique(['quote_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::table('quote_view_count', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique(['quote_id', 'user_id']);
        });
    }
}
