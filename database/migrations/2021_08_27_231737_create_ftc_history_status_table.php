<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseMigration;

class CreateFtcHistoryStatusTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ftc_history_status')) {
            Schema::create('ftc_history_status', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->string('code')->nullable();
                $table->string('text');
                $table->string('text_ar')->nullable();;
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
        Schema::dropIfExists('ftc_history_status');
    }
}
