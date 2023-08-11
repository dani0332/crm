<?php

use App\Enums\RuleTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRuleTypeColumnToRuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rules', function (Blueprint $table) {
            if (! Schema::hasColumn('rules', 'rule_type')) {
                $table->enum('rule_type', RuleTypeEnum::RULE_TYPE_LIST)
                    ->default(1)
                    ->after('is_active')
                    ->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rules', function (Blueprint $table) {
            if (Schema::hasColumn('rules', 'rule_type')) {
                $table->dropColumn('rule_type');
            }
        });
    }
}
