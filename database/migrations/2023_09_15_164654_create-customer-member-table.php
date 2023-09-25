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
                $table->morphs('memberable'); // will create 2 new columns, memberable_id & memberable_type
                $table->string('code', 200)->nullable();
                $table->string('first_name', 50)->nullable();
                $table->string('last_name', 50)->nullable();
                $table->string('gender', 8)->nullable();
                $table->date('dob')->nullable();
                $table->integer('nationality_id')->nullable();
                $table->timestamps();

                $table->foreign('nationality_id')->references('id')->on('nationality')->onDelete('no action');
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
        if (Schema::hasTable('customer_members')) {
            Schema::drop('customer_members');
        }
    }
}
