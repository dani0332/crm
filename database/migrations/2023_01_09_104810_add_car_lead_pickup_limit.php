<?php

use App\Models\ApplicationStorage;
use Illuminate\Database\Migrations\Migration;

class AddCarLeadPickupLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $carLeadPickupLimit = ApplicationStorage::where('key_name', 'CAR_LEAD_PICKUP_LIMIT')->first();
        if ($carLeadPickupLimit == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_LEAD_PICKUP_LIMIT',
                'value' => '100',
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
