<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteDocumentsTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('quote_documents_types')) {
            Schema::create('quote_documents_types', function (Blueprint $table) {
                $table->string('code', '30');
                $table->string('text', '100');
                $table->string('text_ar', '100')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('created_by', '100');
                $table->string('updated_by', '100');
                $table->timestamps();
                $table->primary(array("code"));
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
        Schema::dropIfExists('quote_documents_types');
    }
}
