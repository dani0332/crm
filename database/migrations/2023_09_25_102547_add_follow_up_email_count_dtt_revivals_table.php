<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dtt_revivals', function (Blueprint $table) {
            if (! Schema::hasColumn('dtt_revivals', 'follow_up_email_count')) {
                $table->integer('follow_up_email_count')
                    ->default(0)
                    ->after('is_assigned');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dtt_revivals', function (Blueprint $table) {
            if (Schema::hasColumn('dtt_revivals', 'follow_up_email_count')) {
                $table->dropColumn('follow_up_email_count');
            }
        });
    }
};
