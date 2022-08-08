<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Add2023ToYearOfManufactureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $twentyThree = DB::table('year_of_manufacture')->where('text', '2023')->first();
        if ($twentyThree === null) {
            DB::table('year_of_manufacture')->insert(
                [
                    'text' => '2023',
                    'text_ar' => '۲۰۲۳',
                    'sort_order' => '18',
                    'oman_year' => '72964089-E39F-EC11-B835-005056BD3FE5',
                    'qatar_year' => null,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
