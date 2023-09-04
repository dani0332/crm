<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRuleUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('rule_users')) {
            Schema::create('rule_users', function (Blueprint $table) {
                $table->id();

                $table->foreignId('rule_id')
                    ->constrained('rules')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->timestamps();
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
        Schema::dropIfExists('rule_users');
    }
}
