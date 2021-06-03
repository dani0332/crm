<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('generate_code')) {

    /**
     * Checks if a value exists in an array in a case-insensitive manner
     *
     * @param string $prefix
     * The searched value
     */
    function generate_code($prefix)
    {
        $transaction =  DB::table('transactions')->count();
        $now = \Carbon\Carbon::now();
        $day = $now->day < 10 ? '0'.$now->day:$now->day;
        $month = $now->month < 10 ? '0'.$now->month:$now->month;
        $year = substr($now->year,2);
        return $prefix.$year.$month.$day;
    }
}
