<?php

use App\Models\ApplicationStorage;
use Illuminate\Database\Migrations\Migration;

class CreateItemInAppStorageForCarLeadAllocationRESET extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $carAllocationEndTime = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_END_TIME')->first();
        if ($carAllocationEndTime != null) {
            ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_END_TIME')->update(['key_name' => 'CAR_LEAD_ALLOCATION_TOTAL_RESET']);
        }
    }
}
