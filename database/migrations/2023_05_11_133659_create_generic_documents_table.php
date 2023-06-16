<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGenericDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('generic_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('generic_document_type_id')->nullable();
            $table->foreign('generic_document_type_id')->references('id')->on('generic_document_types');
            $table->string('uuid', '20')->default(DB::raw('(UUID())'));
            $table->morphs('documentable');
            $table->string('name', 255)->nullable();
            $table->string('path', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users');
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
        Schema::dropIfExists('generic_documents');
    }
}
