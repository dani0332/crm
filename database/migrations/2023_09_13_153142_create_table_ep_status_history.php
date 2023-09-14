<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableEpStatusHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('embedded_status_history')) {
            Schema::create('embedded_status_history', function (Blueprint $table) {
                $table->id();
                $table->string('embedded_transaction_code',25)->index();
                $table->foreign('embedded_transaction_code')->references('code')->on('embedded_transactions')
                    ->cascadeOnUpdate();
                $table->string('embedded_transaction_status_code',20)->index();
                $table->foreign('embedded_transaction_status_code')->references('code')->on('lookups')
                ->cascadeOnUpdate();
                $table->dateTime('created_at')->index()->useCurrent();
                $table->dateTime('updated_at')->useCurrent();

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
        Schema::dropIfExists('ep_status_history');
    }
}


