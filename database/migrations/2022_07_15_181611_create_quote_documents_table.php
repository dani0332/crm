<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		if (!Schema::hasTable('quote_documents')) {
            Schema::create('quote_documents', function (Blueprint $table) {
                $table->id();
                //$table->morphs('quote_documentable');
                $table->unsignedBigInteger('quote_documentable_id')->nullable();
                $table->string('quote_documentable_type','255')->nullable();
                // $table->integer('quote_type_id')->nullable();
                // $table->integer('quote_id')->nullable();
                $table->string('doc_name','255')->nullable();
                $table->string('doc_url','255')->nullable();
                $table->string('doc_mime_type','100')->nullable();

                $table->string('document_type_code','30');
                $table->foreign('document_type_code')->references('code')->on('document_types')->onDelete('no action');
                
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');
                
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('no action');
                

                $table->timestamps();
                $table->softDeletes();

                $table->index('quote_documentable_id');
                $table->index('quote_documentable_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_documents');
    }
}
