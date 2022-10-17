<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentDuplicateIdIntoPet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pet_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('pet_quote_request', 'parent_duplicate_quote_id')) {
                $table->string('parent_duplicate_quote_id', 30)->nullable();
                $table->index(['parent_duplicate_quote_id']);
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
        Schema::table('pet_quote_request', function (Blueprint $table) {
            //
        });
    }
}
