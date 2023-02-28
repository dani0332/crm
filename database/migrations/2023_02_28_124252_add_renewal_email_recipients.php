<?php

use App\Models\ApplicationStorage;
use Illuminate\Database\Migrations\Migration;

class AddRenewalEmailRecipients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $masterSwitch = ApplicationStorage::where('key_name', 'RENEWAL_ALLOCATION_LEAD_EMAIL_RECIPIENTS')->first();
        if ($masterSwitch == null) {
            ApplicationStorage::insert([
                'key_name' => 'RENEWAL_ALLOCATION_LEAD_EMAIL_RECIPIENTS',
                'value' => 'ahsan.ashfaq@afia.ae,mshajiu84@gmail.com,johndavid.delacruz@insurancemarket.ae,jerin.mathew@insurancemarket.ae,mark.solon@insurancemarket.ae',
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
