<?php

use App\Enums\IMCRMSearchTypesEnum;
use App\Models\CustomerAdditionalInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

if (! function_exists('generate_code')) {
    /**
     * Checks if a value exists in an array in a case-insensitive manner.
     *
     * @param  string  $prefix
     * The searched value
     */
    function generate_code($prefix)
    {
        $transaction = DB::table('transactions')->count();
        $now = \Carbon\Carbon::now();
        $day = $now->day < 10 ? '0'.$now->day : $now->day;
        $month = $now->month < 10 ? '0'.$now->month : $now->month;
        $year = substr($now->year, 2);

        return $prefix.$year.$month.$day;
    }
}

if (! function_exists('vAbort')) {
    /**
     * abort script execution and return errors in validation format with http status 422
     *
     * @param $messages message string or array of messages
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    function vAbort($messages, $field = 'error')
    {
        if (! is_array($messages)) {
            $messages = [$field => [$messages]];
        }
        throw Illuminate\Validation\ValidationException::withMessages($messages);
    }
}

function get_guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((float) microtime() * 10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid, 12, 4).$hyphen
            .substr($charid, 16, 4).$hyphen
            .substr($charid, 20, 12);

        return $uuid;
    }
}

function mapPhoneNumber($customerPhoneNo)
{
    $customerCorrectPhoneNo = $customerPhoneNo;
    $customerCorrectPhoneNo1 = $customerPhoneNo;
    if (strlen($customerPhoneNo) == 9) { // 563264418 9
        $customerCorrectPhoneNo = '0'.$customerPhoneNo;
    } elseif (strlen($customerPhoneNo) == 12) { // 971563264418 12
        $customerPhoneNo = substr($customerPhoneNo, 3);
        $customerCorrectPhoneNo = '0'.$customerPhoneNo;
    } elseif (strlen($customerPhoneNo) == 13) {
        $customerPhoneNo = substr($customerPhoneNo, 0, 4);

        if ($customerPhoneNo == '9710') { // 9710563264418 13
            $customerCorrectPhoneNo = substr($customerCorrectPhoneNo1, 3);
        }
        if ($customerPhoneNo == '+971') { // +971563264418 13 Working
            $customerPhoneNo = substr($customerCorrectPhoneNo1, 4);
            $customerCorrectPhoneNo = '0'.$customerPhoneNo;
        }
    } elseif (strlen($customerPhoneNo) == 14) {
        $customerPhoneNo = substr($customerPhoneNo, 0, 5);

        if ($customerPhoneNo == '00971') { // 00971563264418 14
            $customerCorrectPhoneNo = substr($customerCorrectPhoneNo1, 5);
            $customerCorrectPhoneNo = '0'.$customerCorrectPhoneNo;
        }
        if ($customerPhoneNo == '+9710') { // +9710563264418 14
            $customerCorrectPhoneNo = substr($customerCorrectPhoneNo1, 4);
        }
    } elseif (strlen($customerPhoneNo) == 15) { // 009710563264418 15
        $customerCorrectPhoneNo = substr($customerPhoneNo, 5);
    } else {
        $customerCorrectPhoneNo = $customerPhoneNo; // 0563264418 10 Working
    }

    return $customerCorrectPhoneNo;
}

function cleanString($string)
{
    $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.

    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function getDataAgainstStatus($modelType, $statusId, $myleads = null)
{
    $result = [];
    if (! $modelType) {
        return $result;
    }
    $nameSpace = '\\App\\Models\\';
    $modelType = $nameSpace.$modelType.'Quote';

    if ($myleads) {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['total_leads'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->count();
            $result['total_premium'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->sum('premium');

            $result['leads_list'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->paginate(10);
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['total_leads'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')
                ->count();
            $result['total_premium'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')
                ->sum('premium');

            $result['leads_list'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')
                ->paginate(10);
        } else {
            $result['total_leads'] = $modelType::where('quote_status_id', $statusId)
                ->count();
            $result['total_premium'] = $modelType::where('quote_status_id', $statusId)
                ->sum('premium');
            $result['leads_list'] = $modelType::where('quote_status_id', $statusId)
                ->paginate(10);
        }
    } else {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['total_leads'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')->count();

            $result['total_premium'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')->sum('premium');

            $result['leads_list'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')->paginate(10);
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['total_leads'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')->count();

            $result['total_premium'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')->sum('premium');

            $result['leads_list'] = $modelType::where('quote_status_id', $statusId)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')->paginate(10);
        } else {
            $result['total_leads'] = $modelType::where('quote_status_id', $statusId)->count();
            $result['total_premium'] = $modelType::where('quote_status_id', $statusId)->sum('premium');
            $result['leads_list'] = $modelType::where('quote_status_id', $statusId)->paginate(10);
        }
    }

    return $result;
}

function getDataAgainstEveryStatus($modelType, $request)
{
    $result = [];
    if (! $modelType) {
        return $result;
    }
    $nameSpace = '\\App\\Models\\';
    $modelType = $nameSpace.$modelType.'Quote';
    if ($request->has('myleads')) {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->paginate(10);
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')
                ->paginate(10);
        } else {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)->where('advisor_id', \Auth::user()->id)->paginate(10);
        }
    } else {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->paginate(10);
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', \Auth::user()->id)
                ->whereNull('previous_quote_id')
                ->paginate(10);
        } else {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)->paginate(10);
        }
    }

    return $result;
}

function getDataAgainstSearchTerm($modelType, $request)
{
    $result = [];
    if (! $request->term) {
        return $result;
    }
    $nameSpace = '\\App\\Models\\';
    $modelType = $nameSpace.$modelType.'Quote';
    if ($modelType == 'Business') {
        if ($request->has('myleads') && $request->myleads) {
            if (Auth::user()->isRenewalAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (?)', [$request->term.'*'])
                    ->where('advisor_id', \Auth::user()->id)
                    ->whereNotNull('previous_quote_id')
                    ->get();
            } elseif (Auth::user()->isNewBusinessAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (?)', [$request->term.'*'])
                    ->where('advisor_id', \Auth::user()->id)
                    ->whereNull('previous_quote_id')
                    ->get();
            } else {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->where('advisor_id', \Auth::user()->id)
                    ->get();
            }
        } else {
            if (Auth::user()->isRenewalAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->whereNotNull('previous_quote_id')
                    ->where('advisor_id', \Auth::user()->id)
                    ->get();
            } elseif (Auth::user()->isNewBusinessAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->whereNull('previous_quote_id')
                    ->where('advisor_id', \Auth::user()->id)
                    ->get();
            } else {
                $result['leads_list'] = $modelType::where('quote_status_id', $status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->get();
            }
        }
    } else {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->whereRaw('MATCH (first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                ->whereNotNull('previous_quote_id')
                ->where('advisor_id', \Auth::user()->id)
                ->get();
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->whereRaw('MATCH (first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                ->whereNull('previous_quote_id')
                ->where('advisor_id', \Auth::user()->id)
                ->get();
        } else {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->whereRaw('MATCH (first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])->get();
        }
    }

    return $result;
}

function getAdditionalInfo($modelType, $quoteId)
{
    $result = CustomerAdditionalInfo::where(['quote_request_id' => $quoteId, 'quote_type' => $modelType.'Quote'])->get();

    return $result;
}

function divideNumber($numerator, $denominator)
{
    return $denominator == 0 ? 0 : ($numerator / $denominator);
}

function getUniqueCode($limit)
{
    return strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit));
}
function get_dob_date_format()
{
    return 'Y-m-d';
}

/**
 * Add search clause to any model's built-in query
 *
 * @param $model
 * @param $request
 * @param $query
 * @param $searchPrefix
 */
function addSearchClauses($model, $request, $query, $searchPrefix)
{
    $searchProperties = $model->searchProperties;
    foreach ($searchProperties as $searchProperty) {
        if (isset($request->$searchProperty)) {
            $propertyMetaData = $model->properties[$searchProperty];
            switch ($propertyMetaData) {
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::LikeSearch):
                    $query = $query->where($searchPrefix.$searchProperty, 'like', '%'.$request->$searchProperty.'%');
                    break;
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::EqualSearch):
                    $query = $query->where($searchPrefix.$searchProperty, $request->$searchProperty);
                    break;
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::DateRange):
                    $dateFrom = Carbon::createFromFormat('Y-m-d', $request[$searchProperty])->startOfDay()->toDateTimeString();
                    $dateTo = Carbon::createFromFormat('Y-m-d', $request[$searchProperty.'_end'])->endOfDay()->toDateTimeString();
                    $query = $query->whereBetween($searchPrefix.$searchProperty, [$dateFrom, $dateTo]);
                    break;
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::MultiSearch):
                    $query = $query->whereIn($searchPrefix.$searchProperty, $request->$searchProperty);
                    break;
                default:
                    break;
            }
        }
    }

    return $query;
}

/**
 * Add orderBy clause to any model's built-in query
 *
 * @param $request
 * @param $query
 * @param $searchPrefix
 */
function addOrderByClauses($request, $query, $searchPrefix)
{
    $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
    $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
    if ($column != '' && $column != 0 && $direction != '') {
        $columnName = $request->get('columns')[$column]['name'];

        return $query->orderBy($searchPrefix.$columnName, $direction);
    } else {
        return $query->orderBy($searchPrefix.'created_at', 'DESC');
    }
}
