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
            if (! Schema::hasColumn('payment_splits', 'premium_authorized')) {
                $table->decimal('premium_authorized', 14, 2)->nullable()->default(null);
            }

            if (! Schema::hasColumn('payment_splits', 'premium_captured')) {
                $table->decimal('premium_captured', 14, 2)->nullable()->default(null);
            }

            if (! Schema::hasColumn('payment_splits', 'premium_refunded')) {
                $table->decimal('premium_refunded', 14, 2)->nullable()->default(null);
            }

            if (! Schema::hasColumn('payment_splits', 'payment_gateway_id')) {
                $table->bigInteger('payment_gateway_id')->nullable()->default(null);
            }

            if (! Schema::hasColumn('payment_splits', 'customer_payment_instrument_id')) {
                $table->bigInteger('customer_payment_instrument_id')->nullable()->default(null);
            }
            if (! Schema::hasColumn('payment_splits', 'payment_status_message')) {
                $table->string('payment_status_message', 500)->nullable()->default(null);
            }

            // Add a foreign key constraint
            $table->foreign('payment_status_id')->references('id')->on('payment_status');

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
