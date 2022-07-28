<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAddColumnsToMyalfredUsersMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('myalfred_users_migration')) {
            Schema::table('myalfred_users_migration', function (Blueprint $table) {
                if (! Schema::hasColumn('myalfred_users_migration', 'code')) {
                    $table->string('code', 250)->nullable();
                }
                if (! Schema::hasColumn('myalfred_users_migration', 'source')) {
                    $table->string('source', 250)->nullable();
                }
            });
        }
        Schema::table('myalfred_users_migration', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('myalfred_users_migration', function (Blueprint $table) {
            //
        });
    }
}
