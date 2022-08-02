<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmUploadLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tm_upload_leads', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', '255')->nullable();
            $table->string('file_path', '255')->nullable();
            $table->integer('total_records')->nullable();
            $table->integer('good')->nullable();
            $table->integer('cannot_upload')->nullable();
            $table->boolean('is_deleted')->default('0');
            $table->boolean('is_submitted')->default('0');
            $table->timestamps();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('modified_by_id')->nullable();
            $table->foreign('modified_by_id')->references('id')->on('users')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tm_upload_leads');
    }
}
