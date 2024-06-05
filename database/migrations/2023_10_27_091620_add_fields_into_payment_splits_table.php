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
        Schema::table('payment_splits', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_splits', 'payment_link')) {
                $table->string('payment_link', '1000')->nullable();
            }

            if (! Schema::hasColumn('payment_splits', 'payment_link_created_at')) {
                $table->dateTime('payment_link_created_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
