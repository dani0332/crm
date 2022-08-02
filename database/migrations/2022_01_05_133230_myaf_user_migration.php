<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MyafUserMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('myalfred_users_migration')) {
            Schema::create('myalfred_users_migration', function (Blueprint $table) {
                $table->id();
                $table->string('signup_url', '250')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
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
        Schema::dropIfExists('myalfred_users_migration');
    }
}
