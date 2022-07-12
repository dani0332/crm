<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentModeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->string('code', 25)->primary();
                $table->string('text');
                $table->string('parent_code', 25)->nullable();
                $table->timestamps();
            });

            Schema::table('payment_methods',function (Blueprint $table){
                $table->foreign('parent_code')->references('code')->on('payment_methods');
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
