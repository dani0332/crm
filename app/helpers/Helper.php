<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Enums\quoteTypeCode;
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


function mapPhoneNumber($customerPhoneNo) {
    $customerCorrectPhoneNo = $customerPhoneNo;
    $customerCorrectPhoneNo1 = $customerPhoneNo;
    if (strlen($customerPhoneNo) == 9) { // 563264418 9
        $customerCorrectPhoneNo = "0" . $customerPhoneNo;
    } else if (strlen($customerPhoneNo) == 12) { // 971563264418 12
        $customerPhoneNo = substr($customerPhoneNo, 3);
        $customerCorrectPhoneNo = "0" . $customerPhoneNo;
    } else if (strlen($customerPhoneNo) == 13) {
        $customerPhoneNo = substr($customerPhoneNo, 0, 4);

        if ($customerPhoneNo == "9710") { // 9710563264418 13
            $customerCorrectPhoneNo = substr($customerCorrectPhoneNo1, 3);
        }
        if ($customerPhoneNo == "+971") { // +971563264418 13 Working
            $customerPhoneNo = substr($customerCorrectPhoneNo1, 4);
            $customerCorrectPhoneNo = "0" . $customerPhoneNo;
        }
    } else if (strlen($customerPhoneNo) == 14) {
        $customerPhoneNo = substr($customerPhoneNo, 0, 5);

        if ($customerPhoneNo == "00971") { // 00971563264418 14
            $customerCorrectPhoneNo = substr($customerCorrectPhoneNo1, 5);
            $customerCorrectPhoneNo = "0" . $customerCorrectPhoneNo;
        }
        if ($customerPhoneNo == "+9710") { // +9710563264418 14
            $customerCorrectPhoneNo = substr($customerCorrectPhoneNo1, 4);
        }
    } else if (strlen($customerPhoneNo) == 15) { // 009710563264418 15
        $customerCorrectPhoneNo = substr($customerPhoneNo, 5);
    } else {
        $customerCorrectPhoneNo = $customerPhoneNo; // 0563264418 10 Working
    }

    return $customerCorrectPhoneNo;
}

function cleanString($string) {
    $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
 }

 function getDataAgainstStatus($modelType, $statusId) {
    $result = [];
    if(!$modelType)
        return $result;
    $nameSpace = '\\App\\Models\\';
    $modelType = $nameSpace .$modelType."Quote";
    $result["total_leads"] = $modelType::where("quote_status_id", $statusId)->count();
    $result["total_premium"] = $modelType::where("quote_status_id", $statusId)->sum("premium");
    $result["leads_list"] = $modelType::where("quote_status_id", $statusId)->paginate(10);
    return $result;
}

function getDataAgainstEveryStatus($modelType, $request) {
    $result = [];
    if(!$modelType)
        return $result;
    $nameSpace = '\\App\\Models\\';
    $modelType = $nameSpace .$modelType."Quote";
    $result["leads_list"] = $modelType::where("quote_status_id", $request->status)->paginate(10);
   
    return $result;
}

function getDataAgainstSearchTerm($modelType, $term, $status) {
    $result = [];
    if(!$term)
        return $result;
    $nameSpace = '\\App\\Models\\';
    $modelType = $nameSpace .$modelType."Quote";
    $result["leads_list"] = $modelType::where("quote_status_id", $status)
    ->Where('code', 'like', '%' . $term )
    ->orWhere('mobile_no', 'like', '%' . $term )
    ->orWhere('email', 'like', '%' . $term )->get();
   
    return $result;
}

