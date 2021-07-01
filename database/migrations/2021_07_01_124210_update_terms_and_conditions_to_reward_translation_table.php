<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTermsAndConditionsToRewardTranslationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reward_translation', function (Blueprint $table) {
            $table->string('terms_and_conditions', 1500)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reward_translation', function (Blueprint $table) {
            $table->dropColumn('terms_and_conditions');
        });
    }
}
