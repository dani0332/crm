<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableRenewalsDump extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('renewals_dump')) {
            Schema::create('renewals_dump', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->string('quote_type', '30');
                $table->integer('cdb_id');
                $table->json('data')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('renewals_dump');
    }
}
