<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnTypeForHighValue extends Migration
{
    public function up()
    {
        Schema::table('tiers', function (Blueprint $table) {
            $table->decimal('max_price', 20, 2)->default(0.00)->change();
        });
    }

    public function down() {}
}
