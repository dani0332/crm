<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttachmentsToClaims extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('attachment_1', '255')->nullable();
            $table->string('attachment_2', '255')->nullable();
            $table->string('attachment_3', '255')->nullable();
            $table->string('attachment_4', '255')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn('attachment_1');
            $table->dropColumn('attachment_2');
            $table->dropColumn('attachment_3');
            $table->dropColumn('attachment_4');
        });
    }
}
