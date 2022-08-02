<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('activites')) {
            Schema::create('activites', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->string('title', 500);
                $table->longText('description');

                $table->bigInteger('quote_request_id')->nullable();
                $table->bigInteger('quote_type_id')->nullable();
                $table->bigInteger('status');

                $table->timestamps();

                $table->unsignedBigInteger('assignee_id')->index();
                $table->foreign('assignee_id')->references('id')->on('users');
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
