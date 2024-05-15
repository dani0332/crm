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
        if (! Schema::hasTable('policy_issuance_status')) {

            Schema::create('policy_issuance_status', function (Blueprint $table) {
                $table->id();
                $table->string('text', 50)->nullable();
                $table->string('text_ar', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->nullable();
                $table->softDeletes('deleted_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_issuance_status');
    }
};
