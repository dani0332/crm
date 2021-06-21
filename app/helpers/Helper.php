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

function get_guid() {
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime()*10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
}
