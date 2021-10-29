<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseMigration;

class CreateTeamsTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id()->autoIncrement();

                $table->bigInteger('lead_id')->nullable(false)->unsigned();
                $table->index('lead_id');
                $table->foreign('lead_id')->references('id')->on('users');

                $table->bigInteger('user_id')->nullable(false)->unsigned();
                $table->index('user_id');
                $table->foreign('user_id')->references('id')->on('users');

                parent::commonFields($table);
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
        Schema::dropIfExists('teams');
    }
}
