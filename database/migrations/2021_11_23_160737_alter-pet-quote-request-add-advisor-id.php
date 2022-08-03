<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPetQuoteRequestAddAdvisorId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pet_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('pet_quote_request', 'uuid')) {
                $table->string('uuid', '100')->default(DB::raw('(UUID())'));
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->foreign('advisor_id')->references('id')->on('users')->onDelete('no action');
            }
        });
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
