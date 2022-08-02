<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('email_status')) {
            Schema::create('email_status', function (Blueprint $table) {
                $table->id();
                $table->integer('quote_type_id')->nullable();
                $table->integer('quote_id')->nullable();
                $table->string('email_address', '100')->nullable();
                $table->string('msg_id', '100')->nullable();
                $table->string('email_status', '30')->nullable();
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
        Schema::dropIfExists('email_status');
    }
}
