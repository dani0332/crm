<?php

use App\Enums\UserStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserStatusLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('log_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id');
            $table->unsignedBigInteger('attribute_id');
            $table->text('value');
            $table->timestamps();

            $table->foreign('log_id')->references('id')->on('logs')->onDelete('cascade');
            $table->foreign('attribute_id')->references('id')->on('log_attributes');
        });

        Schema::create('user_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('status', UserStatusEnum::UserStatusList);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_status_logs');
    }
}
