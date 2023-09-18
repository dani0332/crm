<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('customer_members')) {
            Schema::create('customer_members', function (Blueprint $table) {
                $table->bigIncrements('id')->primary();

                $table->bigInteger('customer_id')->nullable(true);
                $table->index('customer_id');

                $table->string('code', 200)->nullable();
                $table->string('first_name', 50)->nullable();
                $table->string('last_name', 50)->nullable();
                $table->string('email', 150)->nullable(false);
                $table->string('mobile_no', 20)->nullable();
                $table->string('gender', 8)->nullable();
                $table->date('dob')->nullable();
                $table->integer('nationality_id')->nullable();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('customer')->onDelete('no action');

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
        if ( Schema::hasTable('customer_members')) {
            Schema::drop('customer_members');
        }
    }
}
