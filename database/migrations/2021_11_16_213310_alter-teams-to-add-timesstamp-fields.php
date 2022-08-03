<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTeamsToAddTimesstampFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('teams', 'created_at')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->date('created_at')->nullable(false)->default(now());
            });
        }
        if (! Schema::hasColumn('teams', 'updated_at')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->date('updated_at')->nullable(true);
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
        //
    }
}
