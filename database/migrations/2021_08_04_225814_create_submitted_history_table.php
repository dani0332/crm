<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmittedHistoryTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('ftc_history')) {
            Schema::create('ftc_history', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->bigInteger('car_quote_id');
                $table->foreign('car_quote_id')->references('id')->on('car_quote_request');
                $table->string('status', '100')->nullable();
                $table->text('data');
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
        Schema::dropIfExists('ftc_history');
    }
}
