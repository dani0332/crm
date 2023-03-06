<?php

use App\Models\QuoteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateAfiaRenewalText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // AFIA Renewal
        $carAfiaRenewal = QuoteStatus::where('code', 'AfiaRenewal')->first();
        if ($carAfiaRenewal) {
            $carAfiaRenewal->text = 'IM Renewal';
            $carAfiaRenewal->save();
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
