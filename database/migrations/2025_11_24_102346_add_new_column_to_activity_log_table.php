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
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('url')->nullable()->after('description');
            $table->string('feature')->nullable()->after('url');
            $table->string('code')->nullable()->after('feature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('url');
            $table->dropColumn('feature');
            $table->dropColumn('code');
        });
    }
};