<?php

namespace App\Models;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BaseMigration extends Migration {

    public function commonFields($table) {
        $table->timestamps();
        $table->softDeletes();
        $table->string('created_by')->nullable();
        $table->string('updated_by')->nullable();
    }
}
