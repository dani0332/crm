<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		if (!Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table) {
                $table->string('code', '30');
                $table->string('text', '100');
                $table->boolean('is_active')->default(true);
                $table->integer('quote_type_id')->nullable();
                $table->string('folder_path', '255')->nullable();
                $table->string('accepted_files', '255')->nullable();
                $table->integer('max_files')->default('1');
                $table->integer('max_size')->default('5');
                $table->boolean('is_required')->default(false);
                $table->integer('sort_order')->nullable();
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
        Schema::table('document_types', function (Blueprint $table) {
            //
        });
    }
}
