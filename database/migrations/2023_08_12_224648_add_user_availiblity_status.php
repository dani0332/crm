<?php

use App\Enums\UserStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddUserAvailiblityStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function ($table) {
                if (! Schema::hasColumn('users', 'status')) {
                    $table->enum('status', UserStatusEnum::UserStatusList)
                        ->default(UserStatusEnum::UNAVAILABLE);
                }
            });
        }
    }
}
