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
            if (! Schema::hasColumn('payment_splits', 'payment_allocation_status')) {
                $table->string('payment_allocation_status', '1000')->nullable();
            }

            if (! Schema::hasColumn('payment_splits', 'reference')) {
                $table->string('reference', '1000')->nullable();
            }

            if (! Schema::hasColumn('payment_splits', 'captured_at')) {
                $table->dateTime('captured_at')->nullable();
            }

            if (! Schema::hasColumn('payment_splits', 'authorized_at')) {
                $table->dateTime('authorized_at')->nullable();
            }

            if (! Schema::hasColumn('payment_splits', 'discount_value')) {
                $table->float('discount_value', 16, 2)->nullable();
            }

            if (! Schema::hasColumn('payment_splits', 'is_approved')) {
                $table->boolean('is_approved')->default(false);
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
