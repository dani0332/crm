<?php

use App\Enums\quoteTypeCode;
use App\Models\ApplicationStorage;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;

class ChangeSundayResetTimeName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sundayResetTime = ApplicationStorage::where('key_name', 'SUNDAY_CAP_RESET_TIME')->first();
        dd($sundayResetTime);
        if ($sundayResetTime != null) {
            $sundayResetTime->key_name = 'SATURDAY_CAP_RESET_TIME';
            $sundayResetTime->save();
        }
        Team::where('name', quoteTypeCode::Car)->update(['type' => 1]);
        Team::where('name', quoteTypeCode::Home)->update(['type' => 1]);
        Team::where('name', quoteTypeCode::Life)->update(['type' => 1]);
        Team::where('name', quoteTypeCode::Pet)->update(['type' => 1]);
        Team::where('name', quoteTypeCode::Business)->update(['type' => 1]);
        Team::where('name', quoteTypeCode::Health)->update(['type' => 1]);
        Team::where('name', quoteTypeCode::Travel)->update(['type' => 1]);
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
