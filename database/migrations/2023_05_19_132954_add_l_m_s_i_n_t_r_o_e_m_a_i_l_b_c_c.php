<?php

use App\Models\ApplicationStorage;
use Illuminate\Database\Migrations\Migration;

class AddLMSINTROEMAILBCC extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $lmsIntroEmailBcc = ApplicationStorage::where('key_name', 'LMS_INTRO_EMAIL_BCC')->first();
        if ($lmsIntroEmailBcc == null) {
            ApplicationStorage::insert([
                'key_name' => 'LMS_INTRO_EMAIL_BCC',
                'value' => 'motorprocurement@insurancemarket.ae,newleadpool@insurancemarket.ae',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
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
