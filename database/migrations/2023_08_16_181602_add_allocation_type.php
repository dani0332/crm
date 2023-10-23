<?php

use App\Enums\AssignmentTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddAllocationType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('car_quote_request')) {
            Schema::table('car_quote_request', function ($table) {
                if (! Schema::hasColumn('car_quote_request', 'assignment_type')) {
                    $table->enum('assignment_type', AssignmentTypeEnum::AssignmentTypeList)->nullable(true);
                }
            });
        }

        if (Schema::hasTable('health_quote_request')) {
            Schema::table('health_quote_request', function ($table) {
                if (! Schema::hasColumn('health_quote_request', 'assignment_type')) {
                    $table->enum('assignment_type', AssignmentTypeEnum::AssignmentTypeList)->nullable(true);
                }
            });
        }

        if (Schema::hasTable('bike_quote_request')) {
            Schema::table('bike_quote_request', function ($table) {
                if (! Schema::hasColumn('bike_quote_request', 'assignment_type')) {
                    $table->enum('assignment_type', AssignmentTypeEnum::AssignmentTypeList)->nullable(true);
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
