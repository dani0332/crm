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

            if (! Schema::hasColumn('payment_splits', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->default(null);
                $table->foreign('verified_by')->references('id')->on('users')->onDelete('no action');
            }

            if (! Schema::hasColumn('payment_splits', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->default(null);

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
