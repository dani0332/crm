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
        if (Schema::hasColumn('teams', 'created_at'))
        {
            Schema::table('users', function (Blueprint $table)
            {
                $table->date('create_at')->nullable(false)->default(now());
            });
        }
        if (Schema::hasColumn('teams', 'updated_at'))
        {
            Schema::table('users', function (Blueprint $table)
            {
                $table->date('create_at')->nullable(false)->default(now());
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
