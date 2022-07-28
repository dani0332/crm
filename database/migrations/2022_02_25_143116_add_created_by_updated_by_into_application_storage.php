<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByUpdatedByIntoApplicationStorage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('application_storage', function (Blueprint $table) {
            if (! Schema::hasColumn('application_storage', 'created_by')) {
                $table->string('created_by')->nullable();
            }
            if (! Schema::hasColumn('application_storage', 'updated_by')) {
                $table->string('updated_by')->nullable();
            }
            if (! Schema::hasColumn('application_storage', 'deleted_at')) {
                $table->dateTime('deleted_at')->nullable();
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
        Schema::table('application_storage', function (Blueprint $table) {
            //
        });
    }
}
