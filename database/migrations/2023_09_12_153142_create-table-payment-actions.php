<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTablePaymentActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('payment_actions')) {
            Schema::create('payment_actions', function (Blueprint $table) {
                $table->id();
                $table->string('payment_code', 25);
                $table->foreign('payment_code')->references('code')->on('payments')
                    ->cascadeOnUpdate();
                $table->enum('action_type', ['REFUND', 'CAPTURE']);
                $table->decimal('amount', 10, 2);
                $table->boolean('is_fulfilled')->default(false);
                $table->string('created_by', 255);
                $table->string('reason')->nullable();
                $table->boolean('is_manager_approved')->default(false);
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
        Schema::dropIfExists('payment_actions');
    }
}
