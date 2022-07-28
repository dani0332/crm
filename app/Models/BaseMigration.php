<?php

namespace App\Models;

use Illuminate\Database\Migrations\Migration;

class BaseMigration extends Migration
{
    public function commonFields($table)
    {
        $table->timestamps();
        $table->softDeletes();
        $table->string('created_by')->nullable();
        $table->string('updated_by')->nullable();
    }
}
