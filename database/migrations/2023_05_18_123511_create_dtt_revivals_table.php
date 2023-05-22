<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDttRevivalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('dtt_revivals')) {
            Schema::create('dtt_revivals', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->integer('quote_type_id')->nullable();
                $table->index('quote_type_id');
                $table->foreign('quote_type_id')->references('id')->on('quote_type');

                $table->integer('quote_id');
                $table->index('quote_id');

                $table->string('uuid')->unique()->nullable(false);

                $table->boolean('email_sent')->default('0');

                $table->boolean('reply_received')->default('0');

                $table->boolean('is_assigned')->default('0');

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
        Schema::dropIfExists('dtt_revivals');
    }
}
