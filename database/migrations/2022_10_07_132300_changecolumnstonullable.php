<?php

use DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Changecolumnstonullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tiers', function (Blueprint $table) {
            DB::statement('ALTER TABLE tiers MODIFY COLUMN min_price DOUBLE(16,4) DEFAULT 0 NULL');
            DB::statement('ALTER TABLE tiers MODIFY COLUMN max_price DOUBLE(16,4) DEFAULT 0 NULL');
        });
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
