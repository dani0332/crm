<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTierColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('tiers')) {
            Schema::table('tiers', function (Blueprint $table) {
                if (Schema::hasColumn('tiers', 'is_active')) {
                    $table->renameColumn('is_active', 'is_active')->default(true);
                }
                if (Schema::hasColumn('tiers', 'is_tpl')) {
                    $table->renameColumn('is_tpl', 'can_handle_ecommerce');
                }
                if (Schema::hasColumn('tiers', 'is_auto_assignment_enabled')) {
                    $table->renameColumn('is_auto_assignment_enabled', 'can_handle_null_value')->default(false);
                }
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
        //
    }
}
