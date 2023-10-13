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
        if (Schema::hasTable('customer_members')) {
            Schema::table('customer_members', function ($table) {
                if (! Schema::hasColumn('customer_members', 'policy_id')) {
                    $table->unsignedInteger('policy_id')->index()->nullable()->default(null);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_members', function (Blueprint $table) {
            $table->dropColumn('policy_id');
        });
    }
};
