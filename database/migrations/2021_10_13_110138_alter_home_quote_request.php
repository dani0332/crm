<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterHomeQuoteRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('home_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('home_quote_request', 'uuid')) {
                $table->string('uuid', '100')->default(DB::raw('(UUID())'));
                $table->string('policy_number', '50')->nullable();
                $table->bigInteger('previous_quote_id')->nullable();
                $table->bigInteger('advisor_id')->nullable();
                $table->integer('ilivein_accommodation_type_id')->nullable()->change();
                $table->integer('iam_possesion_type_id')->nullable()->change();
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
