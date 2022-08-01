<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClaimsAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claims_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', '255')->nullable();
            $table->string('file_original_name', '255')->nullable();
            $table->string('file_path', '255')->nullable();
            $table->string('file_type', '255')->nullable();
            $table->boolean('is_deleted')->default('0');
            $table->unsignedBigInteger('claims_id')->nullable();
            $table->foreign('claims_id')->references('id')->on('claims')->onDelete('no action');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('modified_by_id')->nullable();
            $table->foreign('modified_by_id')->references('id')->on('users')->onDelete('no action');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('claims_attachments');
    }
}
