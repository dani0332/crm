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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('code', 25)->change();
        });

        Schema::table('payment_splits', function (Blueprint $table) {
            $table->string('code', 25)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
