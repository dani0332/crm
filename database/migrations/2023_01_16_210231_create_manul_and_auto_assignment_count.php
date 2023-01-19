<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManulAndAutoAssignmentCount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('lead_allocation')) {
            Schema::table('lead_allocation', function (Blueprint $table) {
                if (! Schema::hasColumn('lead_allocation', 'manual_assignment_count')) {
                    $table->integer('manual_assignment_count')->default(0);
                }
                if (! Schema::hasColumn('lead_allocation', 'auto_assignment_count')) {
                    $table->integer('auto_assignment_count')->default(0);
                }
            });
        }
    }
}
