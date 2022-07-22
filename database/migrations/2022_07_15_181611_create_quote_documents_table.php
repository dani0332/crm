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
                $table->unsignedBigInteger('quote_documentable_id')->nullable();
                $table->unsignedBigInteger('document_type_id');
                //$table->string('quote_documentable_type','255')->nullable();
                $table->string('doc_name','255')->nullable();
                $table->string('doc_url','255')->nullable();
                $table->string('doc_mime_type','100')->nullable();
                $table->string('document_type_code','30');
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
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
