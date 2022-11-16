<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableCarQuoteBatches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('quote_batches')) {
            Schema::create('quote_batches', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->nullable(false);
                $table->date('start_date')->nullable(false);
                $table->date('end_date')->nullable(false);
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
        //
    }
}
