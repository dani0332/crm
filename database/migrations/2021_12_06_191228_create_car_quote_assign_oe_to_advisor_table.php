<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarQuoteAssignOeToAdvisorTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('car_quote_assign_oe_to_advisor', function (Blueprint $table) {
            $table->id()->autoIncrement();
            parent::commonFields($table);

            $table->unsignedBigInteger('oe_id');
            $table->index('oe_id');
            $table->foreign('oe_id')->references('id')->on('users');

            $table->unsignedBigInteger('advisor_id');
            $table->index('advisor_id');
            $table->foreign('advisor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('car_quote_assign_oe_to_advisor');
    }
}
