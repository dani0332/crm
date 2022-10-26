<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToDocumentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_types', function (Blueprint $table) {
            if (! Schema::hasColumn('document_types', 'description')) {
                $table->text('description')
                    ->after('text')
                    ->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_types', function (Blueprint $table) {
            if (Schema::hasColumn('document_types', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
}
