<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginalNameToQuoteDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_documents', function (Blueprint $table) {
            if (Schema::hasTable('quote_documents') && ! Schema::hasColumn('quote_documents', 'original_name')) {
                $table->string('original_name', 255)->nullable();
            }
        });
    }
}
