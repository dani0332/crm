<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_status_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['1', '2', '3', '4']);
            $table->dateTime('status_changed_at');

            // Indexes for efficient querying
            $table->index('user_id');
            $table->index('status');
            $table->index('status_changed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_status_audit_log');
    }
};
