<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\EmbeddedProductEnum;
use App\Enums\IMCRMSearchTypesEnum;
use App\Enums\LookupsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CustomerAdditionalInfo;
use App\Models\CustomerMembers;
use App\Models\EmbeddedTransaction;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Models\QuoteAdditionalDetail;
use App\Models\QuoteTag;
use App\Models\Team;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\CentralService;
use App\Services\HealthQuoteService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

if (! function_exists('generate_code')) {
    /**
     * Checks if a value exists in an array in a case-insensitive manner.
     *
     * @param  string  $prefix
     *                          The searched value
     */
    function generate_code($prefix)
    {
        $transaction = DB::table('transactions')->count();
        $now = Carbon::now();
        $day = $now->day < 10 ? '0'.$now->day : $now->day;
        $month = $now->month < 10 ? '0'.$now->month : $now->month;
        $year = substr($now->year, 2);

        return $prefix.$year.$month.$day;
    }
}

if (! function_exists('vAbort')) {
    /**
     * abort script execution and return errors in validation format with http status 422.
     *
     * @param  $messages  message string or array of messages
     *
     * @throws ValidationException
     */
    function vAbort($messages, $field = 'error')
    {
        if (! is_array($messages)) {
            $messages = [$field => [$messages]];
        }
        throw ValidationException::withMessages($messages);
    }
}

if (! function_exists('generateUuid')) {
    function generateUuid()
    {
        $client = new Hidehalo\Nanoid\Client;
        $alphabets = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nanoId = $client->formattedId($alphabets, 8);

        return $nanoId;
    }
}

if (! function_exists('storageUrl')) {
    /**
     * get azure storage url.
     *
     * @return string
     */
    function storageUrl()
    {
        return config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
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
    $customerCorrectPhoneNo = $customerCorrectPhoneNo1 = $customerPhoneNo = str_replace(' ', '', trim($customerPhoneNo));

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

function getDataAgainstStatus($modelType, $statusId, Request $request)
{
    // dd($request->all());
    $result = [];

    if (! $modelType) {
        return $result;
    }

    $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));
    $nameSpace = 'App\\Models\\';
    $modelType = (checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

    if (! class_exists($modelType)) {
        return false;
    }

    $modelQueryWithOutAdvisor = $modelType::when($modelType == BusinessQuote::class, function ($businessQuery) {
        $businessQuery->with('businessTypeOfInsurance');
    })->when($modelType == HealthQuote::class, function ($healthQuery) {
        $healthQuery->with('healthCoverFor');
    })
        ->when($modelType == PersonalQuote::class, function ($query) use ($quoteTypeId) {
            $query->where('quote_type_id', $quoteTypeId);
        })
        ->where('quote_status_id', $statusId)
        ->where(function ($query) use ($request, $modelType) {
            getCardViewRequestFilters($query, $request, $modelType);
        });

    $modelQuery = $modelType::when($modelType == BusinessQuote::class, function ($query) {
        $query->with('businessTypeOfInsurance');
    })->when($modelType == HealthQuote::class, function ($healthQuery) {
        $healthQuery->with('healthCoverFor');
    })
        ->when($modelType == PersonalQuote::class, function ($query) use ($quoteTypeId) {
            $query->where('quote_type_id', $quoteTypeId);
        })
        ->where('quote_status_id', $statusId)
        ->where('advisor_id', auth()->user()->id)
        ->where(function ($query) use ($request, $modelType) {
            getCardViewRequestFilters($query, $request, $modelType);
        });

    // Reminder: previous quote id is not available in personal quote

    // if (auth()->user()->isRenewalAdvisor()) {
    //     $result['total_leads'] = $modelQuery->whereNotNull('previous_quote_id')->count();
    //     $result['total_premium'] = $modelQuery->whereNotNull('previous_quote_id')->sum('premium');
    //     $result['leads_list'] = $modelQuery->whereNotNull('previous_quote_id')->paginate(10);

    // } elseif (auth()->user()->isNewBusinessAdvisor()) {
    //     $result['total_leads'] = $modelQuery->whereNull('previous_quote_id')->count();
    //     $result['total_premium'] = $modelQuery->whereNull('previous_quote_id')->sum('premium');
    //     $result['leads_list'] = $modelQuery->whereNull('previous_quote_id')->paginate(10);

    // } else

    if ($modelType == HealthQuote::class && auth()->user()->isCarAdvisor() && auth()->user()->can(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
        $result['total_leads'] = $modelQuery->count();
        $result['total_premium'] = $modelQuery->sum('premium');
        $result['leads_list'] = $modelQuery->paginate(10);
        $result['total_opportunity'] = $modelQueryWithOutAdvisor->sum('price_starting_from');
    } elseif ($modelType == HealthQuote::class && auth()->user()->isCarManager() && auth()->user()->can(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
        $ids = app(HealthQuoteService::class)->walkTree(auth()->user()->id);
        $result['total_leads'] = $modelQueryWithOutAdvisor->whereIn('advisor_id', $ids)->count();
        $result['total_premium'] = $modelQueryWithOutAdvisor->whereIn('advisor_id', $ids)->sum('premium');
        $result['leads_list'] = $modelQueryWithOutAdvisor->whereIn('advisor_id', $ids)->paginate(10);
        $result['total_opportunity'] = $modelQueryWithOutAdvisor->sum('price_starting_from');
    } elseif (auth()->user()->isAdvisor() || auth()->user()->isRenewalAdvisor() || auth()->user()->isNewBusinessAdvisor()) {
        $result['total_leads'] = $modelQuery->count();
        if ($modelType == HealthQuote::class || $modelType == TravelQuote::class) {
            $result['total_premium'] = $modelQueryWithOutAdvisor->where('advisor_id', auth()->user()->id)->sum('premium');
        } else {
            $result['total_premium'] = $modelQueryWithOutAdvisor->where('advisor_id', auth()->user()->id)->sum('price_with_vat');
        }
        $result['leads_list'] = $modelQuery->paginate(10);
        if ($modelType == HealthQuote::class) {
            $result['total_opportunity'] = $modelQuery->sum('price_starting_from');
        }
    } else {
        $result['total_leads'] = $modelQueryWithOutAdvisor->count();
        if ($modelType == HealthQuote::class || $modelType == TravelQuote::class) {
            $result['total_premium'] = $modelQueryWithOutAdvisor->sum('premium');
        } else {
            $result['total_premium'] = $modelQueryWithOutAdvisor->sum('price_with_vat');
        }
        $result['leads_list'] = $modelQueryWithOutAdvisor->paginate(10);
        if ($modelType == HealthQuote::class) {
            $result['total_opportunity'] = $modelQueryWithOutAdvisor->sum('price_starting_from');
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
                ->where('advisor_id', Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->paginate(10);
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', Auth::user()->id)
                ->whereNull('previous_quote_id')
                ->paginate(10);
        } else {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)->where('advisor_id', Auth::user()->id)->paginate(10);
        }
    } else {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', Auth::user()->id)
                ->whereNotNull('previous_quote_id')
                ->paginate(10);
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->where('advisor_id', Auth::user()->id)
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
                    ->where('advisor_id', Auth::user()->id)
                    ->whereNotNull('previous_quote_id')
                    ->get();
            } elseif (Auth::user()->isNewBusinessAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (?)', [$request->term.'*'])
                    ->where('advisor_id', Auth::user()->id)
                    ->whereNull('previous_quote_id')
                    ->get();
            } else {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->where('advisor_id', Auth::user()->id)
                    ->get();
            }
        } else {
            if (Auth::user()->isRenewalAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->whereNotNull('previous_quote_id')
                    ->where('advisor_id', Auth::user()->id)
                    ->get();
            } elseif (Auth::user()->isNewBusinessAdvisor()) {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->whereNull('previous_quote_id')
                    ->where('advisor_id', Auth::user()->id)
                    ->get();
            } else {
                $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                    ->whereRaw('MATCH (company_name, first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                    ->get();
            }
        }
    } else {
        if (Auth::user()->isRenewalAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->whereRaw('MATCH (first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                ->whereNotNull('previous_quote_id')
                ->where('advisor_id', Auth::user()->id)
                ->get();
        } elseif (Auth::user()->isNewBusinessAdvisor()) {
            $result['leads_list'] = $modelType::where('quote_status_id', $request->status)
                ->whereRaw('MATCH (first_name, last_name, code, mobile_no, email) AGAINST (? IN BOOLEAN MODE)', [$request->term.'*'])
                ->whereNull('previous_quote_id')
                ->where('advisor_id', Auth::user()->id)
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
function dateFormat($date): string
{
    return date(env('DATE_DISPLAY_FORMAT'), strtotime($date));
}

/**
 * Add search clause to any model's built-in query.
 */
function addSearchClauses($model, $request, $query, $searchPrefix)
{
    $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
    $searchProperties = $model->searchProperties;
    foreach ($searchProperties as $searchProperty) {
        if (isset($request->$searchProperty)) {
            $propertyMetaData = $model->properties[$searchProperty];
            switch ($propertyMetaData) {
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::LIKE_SEARCH):
                    $query = $query->where($searchPrefix.$searchProperty, 'like', '%'.$request->$searchProperty.'%');
                    break;
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::EQUAL_SEARCH):
                    $query = $query->where($searchPrefix.$searchProperty, $request->$searchProperty);
                    break;
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::DATE_RANGE):
                    $dateFrom = Carbon::createFromFormat($dateFormat, $request[$searchProperty])->startOfDay()->toDateTimeString();
                    $dateTo = Carbon::createFromFormat($dateFormat, $request[$searchProperty.'_end'])->endOfDay()->toDateTimeString();
                    $query = $query->whereBetween($searchPrefix.$searchProperty, [$dateFrom, $dateTo]);
                    break;
                case str_contains($propertyMetaData, IMCRMSearchTypesEnum::MULTI_SEARCH):
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
 * Add orderBy clause to any model's built-in query.
 */
function addOrderByClauses($request, $query, $searchPrefix)
{
    $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
    $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';

    if ($column != '' && $direction != '') {
        $columnName = $request->get('columns')[$column]['name'];

        return $query->orderBy($searchPrefix.$columnName, $direction);
    } else {
        return $query->orderBy($searchPrefix.'created_at', 'DESC');
    }
}

function formatAmount($value, $decimals = 2, $appendPrefix = true)
{
    $value = number_format($value, $decimals);

    return ($appendPrefix) ? 'AED '.$value : $value;
}

function generateRouteNames($prefix)
{
    return [
        'index' => $prefix.'-list',
        'create' => $prefix.'-create',
        'store' => $prefix.'-store',
        'show' => $prefix.'-show',
        'edit' => $prefix.'-edit',
        'update' => $prefix.'-update',
        'destroy' => $prefix.'-delete',
        'search' => $prefix.'-search',
    ];
}

if (! function_exists('isCarLostStatus')) {
    function isCarLostStatus($quoteStatus): bool
    {
        return $quoteStatus == QuoteStatusEnum::CarSold || $quoteStatus == QuoteStatusEnum::Uncontactable;
    }
}

if (! function_exists('createCdnUrl')) {
    function createCdnUrl($path): string
    {
        return config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/'.$path;
    }
}

if (! function_exists('getAutomationUser')) {
    function getAutomationUser(): array
    {
        return ['qa_automation@myalfred.com'];
    }
}

if (! function_exists('formatLandlineNumber')) {
    function formatLandlineNumber($landlineNumber)
    {
        return preg_replace(
            "/.*(\d{2})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4})/",
            '$1 $2 $3',
            mapPhoneNumber($landlineNumber)
        );
    }
}

if (! function_exists('formatMobileNumber')) {
    function formatMobileNumber($mobileNumber)
    {
        return preg_replace(
            "/.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4})/",
            '$1 $2 $3',
            mapPhoneNumber($mobileNumber)
        );
    }
}

if (! function_exists('checkPersonalQuotes')) {
    function checkPersonalQuotes($quoteType)
    {
        return in_array($quoteType, [
            QuoteTypes::BIKE->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
            QuoteTypes::PET->value,
            QuoteTypes::YACHT->value,
        ]);
    }
}

if (! function_exists('getBase64FileInfo')) {
    function getBase64FileInfo($base64File)
    {
        $fileSize = strlen($base64File);
        @[$type, $file_data] = explode(';', $base64File);
        @[, $file_data] = explode(',', $file_data);
        @[, $fileMimeType] = explode(':', $type);
        @[, $extension] = explode('/', $fileMimeType);

        return [$extension, $fileMimeType, $file_data, $fileSize];
    }
}

if (! function_exists('sanitizeFileName')) {
    function sanitizeFileName($fileName)
    {
        // Remove any Unicode control characters, including non-breaking spaces
        $fileName = preg_replace('/[\x{00}-\x{1F}\x{7F}\x{A0}]/u', '', $fileName);

        // Remove any Unicode control characters
        $fileName = preg_replace('/[[:cntrl:]]/', '', $fileName);

        // Remove any unwanted characters
        $fileName = preg_replace('/[^\p{L}\p{N}\s\-\_\.]/u', '', $fileName);

        // Remove leading and trailing whitespaces
        $fileName = trim($fileName);

        // Replace whitespace with underscores
        $fileName = preg_replace('/\s+/', '_', $fileName);

        // Replace multiple underscores with a single underscore
        $fileName = preg_replace('/_+/', '_', $fileName);

        return $fileName;
    }
}

if (! function_exists('getQueryForLogWithBindings')) {
    function getQueryForLogWithBindings(Builder $builder)
    {
        $addSlashes = str_replace('?', "'?'", $builder->toSql());

        return vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());
    }
}

if (! function_exists('formatMobileNo')) {
    function formatMobileNo($mobile)
    {
        return preg_replace('/^(?:\+?971|0)?/', '+971', str_replace(' ', '', $mobile));
    }
}

if (! function_exists('formatMobileNoWithoutPlus')) {
    function formatMobileNoWithoutPlus($mobile)
    {
        // Remove spaces from the mobile number
        $mobile = str_replace(' ', '', $mobile);

        // If the number starts with +971, 971, 92, or 91, return it as is
        if (preg_match('/^(?:\+?971|92|91)/', $mobile)) {
            return ltrim($mobile, '+'); // Remove '+' if present, but keep the number unchanged
        }

        // If the number starts with +0 or 0, replace it with 971
        if (preg_match('/^\+?0/', $mobile)) {
            return preg_replace('/^\+?0/', '971', $mobile);
        }

        // If the number does not match any pattern, add 971 as default
        return '971'.ltrim($mobile, '+');
    }
}

if (! function_exists('removeCountryCode')) {
    function removeCountryCode($mobile)
    {
        $mobile = preg_replace('/^\+971|0(?=\d{9})/', '', $mobile);

        if (substr($mobile, 0, 1) !== '0') {
            return '0'.$mobile;
        }

        return $mobile;
    }
}

if (! function_exists('formatMobileNoDisplay')) {
    function formatMobileNoDisplay($mobile)
    {
        $mobile = removeCountryCode($mobile);

        return preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '$1 $2 $3', $mobile);
    }
}

if (! function_exists('formatLandlineDisplay')) {
    function formatLandlineDisplay($landline)
    {
        $landline = removeCountryCode($landline);

        return preg_replace('/^(\d{2})(\d{3})(\d{4})$/', '$1 $2 $3', $landline);
    }
}

if (! function_exists('getRepositoryObject')) {
    function getRepositoryObject($quoteType)
    {
        if (checkPersonalQuotes($quoteType)) {
            $quoteType = QuoteTypes::PERSONAL->value;
        }

        $quoteType = ucfirst($quoteType);

        return 'App\\Repositories\\'.$quoteType.'QuoteRepository';
    }
}

if (! function_exists('getServiceObject')) {
    function getServiceObject($quoteType)
    {
        if (checkPersonalQuotes($quoteType)) {
            $quoteType = QuoteTypes::PERSONAL->value;
        }

        $quoteType = ucfirst($quoteType);

        return 'App\\Services\\'.$quoteType.'QuoteService';
    }
}

if (! function_exists('checkModifiedRecord')) {
    function checkModifiedRecord($firstDate, $secondDate): bool
    {
        return Carbon::parse($firstDate)->format(config('constants.datetime_format')) !==
            Carbon::parse($secondDate)->format(config('constants.datetime_format'));
    }
}

if (! function_exists('dateQueryFilter')) {
    function dateQueryFilter($firstDate, $secondDate, $clauseTypeBetween = true): array
    {
        $firstDate = date(config('constants.DATE_FORMAT_ONLY').' 00:00:00', strtotime($firstDate));
        $secondDate = date(config('constants.DATE_FORMAT_ONLY').' 23:59:59', strtotime($secondDate));
        $currentDate = Carbon::now()->format(config('constants.DB_DATE_FORMAT_MATCH'));

        if ($clauseTypeBetween) {
            return [$firstDate, $secondDate];
        }

        return [$currentDate, $currentDate];
    }
}

if (! function_exists('addDaysExcludeWeekend')) {
    function addDaysExcludeWeekend($daysToAdd, $date = null)
    {
        // $date = $date ?? Carbon::now();
        $date = Carbon::parse($date) ?? Carbon::now();
        $date = $date->addDays($daysToAdd);

        if ($date->isWeekend()) {
            $date = $date->addDays(2);
        }

        return $date;
    }
}

if (! function_exists('getIMLogo')) {
    function getIMLogo($isPDF = false)
    {
        $imLogo = 'images/logo-new.png';

        return $isPDF ? public_path($imLogo) : asset($imLogo);
    }
}
if (! function_exists('mimeContentType')) {
    function mimeContentType($ext = null, $mimeType = null)
    {
        $mime_types = [ // images
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
        ];

        if (! empty($ext)) {
            array_key_exists($ext, $mime_types);

            return $mime_types[$ext];
        }
        if (! empty($mimeType)) {
            return array_search($mimeType, $mime_types);
        }
    }
}
if (! function_exists('checkAuthUserRole')) {
    function checkAuthUserRole()
    {

        if (! Auth::check()) {
            return false;
        }

        if (Auth::user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::HealthManager, RolesEnum::BusinessManager, RolesEnum::HomeManager, RolesEnum::LifeManager, RolesEnum::PetManager, RolesEnum::YachtManager, RolesEnum::TravelManager, RolesEnum::BikeManager, RolesEnum::CycleManager, RolesEnum::JetskiManager])) {
            return true;
        } else {
            return false;
        }
    }
}

if (! function_exists('apiResponse')) {
    function apiResponse($data, $statusCode = 200, $message = null)
    {
        // If the data is an instance of Exception, handle it separately
        if ($data instanceof Exception) {
            $statusCode = 500;
            $message = $data->getMessage();
            $data = null;
        }

        if ($data instanceof ValidationException) {
            $statusCode = 422;
            $message = $data->errors();
            $data = null;
        }

        // If the status code is 400, set a default error message if none is provided
        if ($statusCode === 400 && $message === null) {
            $message = 'Missing or invalid parameters.';
        }

        return response()->json([
            'data' => $data,
            'message' => $message,
            'status' => $statusCode,
        ], $statusCode);
    }

    if (! function_exists('generateQuoteMemberCode')) {
        function generateQuoteMemberCode($customerType, $customerEntityID)
        {
            $quoteMemberCount = CustomerMembers::where([
                'customer_type' => $customerType,
                'customer_entity_id' => $customerEntityID,
            ])->count();

            return ($customerType == CustomerTypeEnum::Individual) ?
                CustomerTypeEnum::IndividualShort.'-'.$customerEntityID.'-'.(++$quoteMemberCount) :
                CustomerTypeEnum::EntityShort.'-'.$customerEntityID.'-'.(++$quoteMemberCount);
        }
    }
}

if (! function_exists('strToFloat')) {
    function strToFloat($value, $isNegative = false): float
    {
        if ($isNegative) {
            $value = $value > 0 ? -$value : $value;
        }

        return floatval(str_replace(',', '', $value));
    }
}
if (! function_exists('getCardViewRequestFilters')) {
    function getCardViewRequestFilters($partialQuery, Request $request, $modelType)
    {
        // Mapping model types to relationships and column names
        $modelTypeMappings = [
            HealthQuote::class => [
                'relation' => 'healthQuoteRequestDetail',
                'column' => 'advisor_assigned_date',
            ],
            HomeQuote::class => [
                'relation' => 'homeQuoteRequestDetail',
                'column' => 'advisor_assigned_date',
            ],
            PersonalQuote::class => [
                'relation' => 'quoteDetail',
                'column' => 'advisor_assigned_date',
            ],
            BusinessQuote::class => [
                'relation' => 'businessQuoteRequestDetail',
                'column' => 'advisor_assigned_date',
            ],
        ];

        if (array_key_exists($modelType, $modelTypeMappings)) {
            $mapping = $modelTypeMappings[$modelType];

            // Handle HealthQuote type filtering
            if (! empty($request->assigned_to_date_start) && ! empty($request->assigned_to_date_end)) {
                $dateFrom = date('Y-m-d 00:00:00', strtotime($request['assigned_to_date_start']));
                $dateTo = date('Y-m-d 23:59:59', strtotime($request['assigned_to_date_end']));

                // Dynamically applying filter for the model type
                $partialQuery->whereHas($mapping['relation'], function ($query) use ($dateFrom, $dateTo, $mapping) {
                    $query->whereBetween($mapping['column'], [$dateFrom, $dateTo]);
                });
            }

            // Handle HomeQuote type filtering
            if (! empty($request->advisor_assigned_date)) {
                $dateArray = $request['advisor_assigned_date'];

                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();

                // Dynamically applying filter for the model type
                $partialQuery->whereHas($mapping['relation'], function ($query) use ($dateFrom, $dateTo, $mapping) {
                    $query->whereBetween($mapping['column'], [$dateFrom, $dateTo]);
                });
            }

            $partialQuery->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
        }

        if (isset($request->code) && $request->code != '') {
            $partialQuery->where('code', $request->code);
        }

        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $partialQuery->where('renewal_batch', $request->renewal_batch);
        }

        if (isset($request->quote_status) && is_array($request->quote_status) && count($request->quote_status) > 0) {
            $partialQuery->whereIn('quote_status_id', $request->quote_status);
        }

        if (isset($request->first_name) && $request->first_name != '') {
            $partialQuery->where('first_name', $request->first_name);
        }
        if (isset($request->last_name) && $request->last_name != '') {
            $partialQuery->where('last_name', $request->last_name);
        }
        if (isset($request->email) && $request->email != '') {
            $partialQuery->where('email', $request->email);
        }

        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $partialQuery->where('mobile_no', $request->mobile_no);
        }

        if (! empty($request->created_at_start) && ! empty($request->created_at_end)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));

            $partialQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->is_ecommerce)) {
            $isEcommerce = $request->is_ecommerce == 'Yes' ? 1 : 0;
            $partialQuery->where('is_ecommerce', $isEcommerce);
        }

        if (isset($request->assignment_type) && ! empty($request->assignment_type)) {
            $partialQuery->where('assignment_type', $request->assignment_type);
        }

        if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            $partialQuery->where(function ($query) use ($request) {
                $query->where('policy_number', $request->previous_quote_policy_number)
                    ->orWhere('previous_quote_policy_number', $request->previous_quote_policy_number);
            });
        }

        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $partialQuery->where('renewal_batch', $request->renewal_batch);
        }

        if (isset($request->sub_team) && $request->sub_team != '') {
            $partialQuery->where('health_team_type', $request->sub_team);
        }

        if ($request->hasAny(['created_at_start', 'created_at_end']) && $request->filled(['created_at_start', 'created_at_end'])) {
            $partialQuery->whereBetween('created_at', dateQueryFilter($request->created_at_start, $request->created_at_end));
        }

        if ($request->has('is_cold') && $request->filled('is_cold')) {
            $partialQuery->where('is_cold', true);
        }

        if ($request->has('is_stale') && $request->filled('is_stale')) {
            $partialQuery->whereNotNull('stale_at');
        }

        if ($request->has('payment_status') && $request->filled('payment_status') && count($request->payment_status)) {
            $partialQuery->whereIn('payment_status_id', $request->payment_status);
        }

        if (isset($request->is_renewal) && $request->is_renewal != '') {
            if ($request->is_renewal == quoteTypeCode::yesText) {
                $partialQuery->whereNotNull('previous_quote_policy_number');
            }
            if ($request->is_renewal == quoteTypeCode::noText) {
                $partialQuery->whereNull('previous_quote_policy_number');
            }
        }

        if ($request->has('last_modified_date') && $request->filled('last_modified_date')) {
            $dateArray = $request['last_modified_date'];

            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $partialQuery->whereBetween('updated_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->advisors) && ! empty($request->advisors)) {
            $advisors = (array) $request->advisors;
            if (! empty($advisors)) {
                $partialQuery->whereIn('advisor_id', $advisors)->whereNotNull('advisor_id');
            }
        }
    }
}

if (! function_exists('isEmailCampaignEnabled')) {
    function isEmailCampaignEnabled(): bool
    {
        return getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN_ENABLED) == '1';
    }
}

if (! function_exists('getMyAlfredCampaign')) {
    function getMyAlfredCampaign($campaignId)
    {
        if (! isEmailCampaignEnabled()) {
            return null;
        }

        return Cache::remember("MA_CAMPAIGN_{$campaignId}", now()->addHours(24), function () use ($campaignId) {
            try {
                $response = Http::timeout(20)->retry(3, 3000)->get(config('constants.MA_V1_ENDPOINT')."/campaigns/{$campaignId}");
                if ($response->ok()) {
                    $response = $response->object();

                    if ($response->data && $response->data->isActive) {
                        return $response;
                    }
                }
            } catch (Exception $e) {
                Log::error('getMyAlfredCampaign Error: '.$e->getMessage().$e->getTraceAsString());
            }

            return null;
        });
    }
}

if (! function_exists('isMyAlfredCampaignEnabled')) {
    function isMyAlfredCampaignEnabled($campaignId): bool
    {
        $campaign = getMyAlfredCampaign($campaignId);
        if (! $campaign) {
            return false;
        }

        if (property_exists($campaign->data, 'startDate') && property_exists($campaign->data, 'endDate')) {
            return today()->between($campaign->data->startDate, $campaign->data->endDate);
        }

        return false;
    }
}

if (! function_exists('getAppStorageValueByKey')) {
    function getAppStorageValueByKey($keyName, $default = false)
    {
        $query = ApplicationStorage::select('value')->where('key_name', $keyName)->first();

        if (! $query) {
            return $default;
        }

        return $query->value;
    }
}

if (! function_exists('getAlfredEligibleCustomers')) {
    function getAlfredEligibleCustomers($data)
    {
        try {
            $username = config('constants.MA_V1_USERNAME');
            $password = config('constants.MA_V1_PASSWORD');
            $basicAuth = base64_encode("$username:$password");

            $response = Http::timeout(20)->retry(2, 3000)
                ->withHeaders([
                    'Authorization' => 'Basic '.$basicAuth,
                ])
                ->post(config('constants.MA_V1_ENDPOINT').'/internal/wfs/get-remaining-scratches', ['data' => $data]);

            if ($response->ok()) {
                $response = $response->object();

                if ($response->data) {
                    return $response;
                }
            }
        } catch (Exception $e) {
            Log::error('getAlfredEligibleCustomers Error: '.$e->getMessage().$e->getTraceAsString());
        }

        return null;
    }
}

if (! function_exists('isValidEmail')) {
    function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (! function_exists('isValidDate')) {
    function isValidDate($date): bool
    {
        return ! empty($date)
            && $date != '0000-00-00 00:00:00'
            && $date != '0000-00-00';
    }
}

if (! function_exists('isValidTeamForLOBAdvisor')) {
    function isValidTeamForLOBAdvisor($teams, $allowed_teams)
    {
        $teams = collect($teams)->pluck('name');
        $matching_teams = collect($teams)->intersect($allowed_teams);
        if ($matching_teams->isNotEmpty()) {
            return true;
        }

        return false;
    }
}
if (! function_exists('isAllowedInDuplicateLOBList')) {
    function isAllowedInDuplicateLOBList($quoteType, $code)
    {
        return app(CentralService::class)->duplicateAllowedLobsList($quoteType, $code);
    }
}

if (! function_exists('removeSpaces')) {
    function removeSpaces($number)
    {
        return str_replace(' ', '', $number);
    }
}

if (! function_exists('hasAnyPermission')) {
    function hasAnyPermission($_permissions)
    {
        $permissions = is_array($_permissions) ? $_permissions : func_get_args();
        $permissions = implode('|', $permissions);

        return "permission:{$permissions}";
    }
}

if (! function_exists('hasAnyRole')) {
    function hasAnyRole($_roles)
    {
        $roles = is_array($_roles) ? $_roles : func_get_args();
        $roles = implode('|', $roles);

        return "role:{$roles}";
    }
}

if (! function_exists('checkForRoleOrTeam')) {
    function checkForRoleOrTeam($user_id, $type)
    {
        $with = [];
        switch ($type) {
            case 'role':
                $with[] = 'usersroles:id,name';
                break;
            case 'team':
                $with[] = 'teams:id,name';
                break;
            case 'both':
                $with[] = 'usersroles:id,name';
                $with[] = 'teams:id,name';
                break;
        }

        // Fetch user with conditional eager loading
        $user = User::where('id', $user_id)->with($with)->first();

        if ($type === 'role') {
            return $user->usersroles;
        } elseif ($type === 'team') {
            return $user->teams;
        } else {
            return [
                'roles' => $user->usersroles,
                'teams' => $user->teams,
            ];
        }
    }
}

if (! function_exists('arrayKeysToCamelCase')) {
    /**
     * Recursively transform array keys to camelCase.
     *
     * @return array
     */
    function arrayKeysToCamelCase(array $array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = Str::camel($key);

            if (is_array($value)) {
                $value = arrayKeysToCamelCase($value);
            }

            $result[$newKey] = $value;
        }

        return $result;
    }
}

if (! function_exists('transformKeys')) {
    /**
     * Transform array keys based on given mappings.
     *
     * @return array
     */
    function transformKeys(array $array, array $keyMappings = [])
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (array_key_exists($key, $keyMappings)) {
                $newKey = $keyMappings[$key];
            } else {
                $newKey = $key;
            }

            $result[$newKey] = $value;
        }

        return $result;
    }
}

if (! function_exists('isValidDate')) {
    function isValidDate($date): bool
    {
        return ! empty($date)
            && $date != '0000-00-00 00:00:00'
            && $date != '0000-00-00';
    }
}

if (! function_exists('getAssignmentTypeText')) {
    function getAssignmentTypeText($assignmentType)
    {
        $assignmentText = '';
        switch ($assignmentType) {
            case 1:
                $assignmentText = 'System Assigned';
                break;
            case 2:
                $assignmentText = 'System ReAssigned';
                break;
            case 3:
                $assignmentText = 'Manual Assigned';
                break;
            case 4:
                $assignmentText = 'Manual ReAssigned';
                break;
            case 5:
                $assignmentText = 'Bought Lead';
                break;
            case 6:
                $assignmentText = 'ReAssigned as Bought Lead';
                break;
            default:
                break;
        }

        return $assignmentText;
    }
}

if (! function_exists('getEmailCampaignBanner')) {
    function getEmailCampaignBanner()
    {
        $emailCampaignBanner = null;
        $emailCampaignBannerRedirectUrl = null;

        $campaign = getMyAlfredCampaign(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN));
        if ($campaign) {
            if (property_exists($campaign, 'banners') && property_exists($campaign->banners, 'buyPolicy')) {
                $emailCampaignBanner = $campaign->banners->buyPolicy;
            }
            if (property_exists($campaign, 'landingPage')) {
                $emailCampaignBannerRedirectUrl = $campaign->landingPage;
            }
        }

        return [$emailCampaignBanner, $emailCampaignBannerRedirectUrl];
    }
}

if (! function_exists('getQuoteUsingSubject')) {
    function getQuoteUsingSubject(string $input)
    {
        $words = preg_split('/\s+/', trim($input));

        $getTypeAndUUID = function (QuoteTypes $quoteType) use ($words) {
            $uuid = collect($words)->first(fn ($value) => Str::startsWith($value, $quoteType->shortCode()));

            if ($uuid) {
                return [$quoteType, Str::afterLast($uuid, '-')];
            }

            return null;
        };

        foreach (QuoteTypes::cases() as $quoteType) {
            if ($quoteType === QuoteTypes::PERSONAL) {
                continue;
            }

            $data = $getTypeAndUUID($quoteType);

            if ($data) {
                return $data;
            }
        }

        return null;
    }
}

if (! function_exists('getManagersByUser')) {
    function getManagersByUser($userId)
    {
        $managerIds = DB::table('user_manager')->where('user_id', $userId)->get()->pluck('manager_id');

        return User::whereIn('id', $managerIds)->where('is_active', 1)->get();
    }
}

if (! function_exists('roundNumber')) {
    function roundNumber($number, $precision = 2)
    {
        return round($number, $precision);
    }
}

if (! function_exists('getLookupsEnum')) {
    function getLookupsEnum(): array
    {
        return array_combine(
            array_map(fn ($case) => $case->name, LookupsEnum::cases()),
            array_map(fn ($case) => $case->value, LookupsEnum::cases())
        );
    }
}

if (! function_exists('getCourierQuote')) {
    function getCourierQuote($quote, $quoteTypeId, $quoteStatuses = [])
    {
        try {
            $quoteModel = get_class($quote);
            $model = app($quoteModel);
            $table = $model->getTable();

            $quote = $model::addSelect([
                "{$table}.id as quote_id",
                "{$table}.uuid as quote_uuid",
                "{$table}.created_at as quote_created_at",
                "{$table}.policy_number as insurance_policy_number",
                'payments.code as ep_ref_id',
                'payments.captured_at as payment_captured_at',
                'customer.first_name as client_first_name',
                'customer.last_name as client_last_name',
                'customer.email as client_email',
                "{$table}.mobile_no as client_phone_number",
                'customer_addresses.type as courier_address_type',
                'customer_addresses.office_number as courier_address_office_number',
                'customer_addresses.floor_number as courier_address_floor_number',
                'customer_addresses.building_name as courier_address_building_name',
                'customer_addresses.street as courier_address_street',
                'customer_addresses.area as courier_address_area',
                'customer_addresses.city as courier_address_city',
                'customer_addresses.landmark as courier_address_landmark',
            ])
                ->when(! in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Travel]), function ($q) use ($table, $quoteTypeId) {
                    $q->addSelect([
                        'emirates.code as emirate_code',
                        'emirates.text as emirate_text',
                    ])
                        ->leftJoin('emirates', 'emirates.id', '=', match ($quoteTypeId) {
                            QuoteTypeId::Health => "{$table}.emirate_of_your_visa_id",
                            default => "{$table}.emirate_of_registration_id"
                        });
                })
                ->when(! empty($quoteStatuses) && is_array($quoteStatuses), function ($q) use ($table, $quoteStatuses) {
                    $q->whereIn("{$table}.quote_status_id", $quoteStatuses);
                })
                ->leftJoin('customer_addresses', function (JoinClause $join) use ($table, $quoteTypeId) {
                    $join->on('customer_addresses.quote_uuid', '=', "{$table}.uuid")
                        ->where('customer_addresses.quote_type_id', $quoteTypeId);
                })
                ->join('customer', 'customer.id', '=', "{$table}.customer_id")
                ->join('embedded_transactions', function (JoinClause $join) use ($table, $quoteModel) {
                    $join->on('embedded_transactions.quote_request_id', '=', "{$table}.id")
                        ->where('embedded_transactions.quote_request_type', $quoteModel)
                        ->join('payments', function (JoinClause $subJoin) {
                            $subJoin->on('payments.paymentable_id', '=', 'embedded_transactions.id')
                                ->where('payments.paymentable_type', EmbeddedTransaction::class);
                        })
                        ->join('embedded_product_options', function (JoinClause $subJoin) {
                            $subJoin->on('embedded_product_options.id', '=', 'embedded_transactions.product_id')
                                ->join('embedded_products', function (JoinClause $sub) {
                                    $sub->on('embedded_products.id', '=', 'embedded_product_options.embedded_product_id')
                                        ->where('embedded_products.short_code', EmbeddedProductEnum::COURIER);
                                });
                        });
                })
                ->find($quote->id);

            if ($quote) {
                $quoteType = QuoteTypes::getName($quoteTypeId);

                $whatsappConsent = false;
                $quoteAdditionalDetail = QuoteAdditionalDetail::where('quote_uuid', $quote->quote_uuid)->where(function ($q) use ($quoteType) {
                    $q->where('quote_type_id', (int) $quoteType?->id());
                    $q->orWhere('quote_type_id', $quoteType?->id());
                })->first();

                if ($quoteAdditionalDetail) {
                    $whatsappConsent = isset($quoteAdditionalDetail->flags['whatsapp_consent']) ? $quoteAdditionalDetail->flags['whatsapp_consent'] : false;
                }

                return [
                    'quote' => [
                        'id' => $quote->quote_id,
                        'uuid' => $quote->quote_uuid,
                        'created_at' => $quote->quote_created_at,
                        'policy_number' => strtolower($quote->insurance_policy_number) === 'null' ? null : $quote->insurance_policy_number,
                        'quote_type_id' => $quoteType?->id(),
                        'line_of_business' => $quoteType?->value,
                        'link' => $quoteType?->url($quote->quote_uuid),
                    ],
                    'payment' => [
                        'ref_id' => $quote->ep_ref_id,
                        'captured_at' => $quote->payment_captured_at,
                    ],
                    'customer' => [
                        'name' => trim("{$quote->client_first_name} {$quote->client_last_name}"),
                        'first_name' => $quote->client_first_name,
                        'last_name' => $quote->client_last_name,
                        'email' => $quote->client_email,
                        'phone_number' => $quote->client_phone_number,
                        'is_whatsapp_enabled' => $whatsappConsent,
                    ],
                    'emirate' => [
                        'name' => $quote->emirate_text ?? null,
                        'code' => $quote->emirate_code ?? null,
                    ],
                    'with_address' => (bool) $quote->courier_address_type,
                    'courier_address' => [
                        'type' => $quote->courier_address_type,
                        'office_number' => $quote->courier_address_office_number,
                        'floor_number' => $quote->courier_address_floor_number,
                        'building_name' => $quote->courier_address_building_name,
                        'street' => $quote->courier_address_street,
                        'area' => $quote->courier_address_area,
                        'city' => $quote->courier_address_city,
                        'landmark' => $quote->courier_address_landmark,
                    ],
                ];
            }

            return null;
        } catch (Exception $e) {
            Log::error('getCourierQuote: Error retrieving quote: '.$e->getMessage());

            return null;
        }
    }
}

if (! function_exists('isVatApplied')) {
    function isVatApplied($modelType): bool
    {
        $vatEnabledQuotes = [
            quoteTypeCode::Health,
            quoteTypeCode::Business,
            quoteTypeCode::Pet,
            quoteTypeCode::Cycle,
            quoteTypeCode::Bike,
        ];
        if (in_array($modelType, $vatEnabledQuotes)) {
            return true;
        }

        return false;
    }
}

if (! function_exists('getTeamId')) {
    /**
     * Get the ID of a team by its name.
     */
    function getTeamId(string $teamName): int
    {
        try {
            $team = Team::where('name', $teamName)->first();

            return optional($team)->id ?? 0;
        } catch (Exception $e) {
            Log::error("Error retrieving team ID for team name: {$teamName}", ['exception' => $e]);

            return 0;
        }
    }
}

if (! function_exists('isLeadSic')) {
    function isLeadSic(string $uuid): bool
    {
        try {
            $isSic = QuoteTag::where('quote_uuid', $uuid)->where('name', 'SIC')->exists();

            return $isSic;
        } catch (Exception $e) {
            Log::error("Failed to check SIC status for quote_uuid: {$uuid}. Error: ".$e->getMessage());

            return false;
        }
    }
}

if (! function_exists('getCarQuoteByUuid')) {
    function getCarQuoteByUuid(string $uuid): ?CarQuote
    {
        try {
            // Fetch the CarQuote model using the provided UUID
            return CarQuote::where('uuid', $uuid)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Log if the CarQuote was not found
            Log::warning("CarQuote not found for UUID: {$uuid}");

            return null;
        } catch (Exception $e) {
            // Log any other unexpected errors
            Log::error("Error retrieving CarQuote for UUID: {$uuid}. Error: {$e->getMessage()}");

            return null;
        }
    }
}
if (! function_exists('getWhatsappConsent')) {
    function getWhatsappConsent(QuoteTypes $quoteType, string $uuid): bool
    {
        $whatsappConsent = false;
        $quoteAdditionalDetail = QuoteAdditionalDetail::where('quote_uuid', $uuid)->where(function ($q) use ($quoteType) {
            $q->where('quote_type_id', (int) $quoteType?->id());
            $q->orWhere('quote_type_id', $quoteType?->id());
        })->first();

        if ($quoteAdditionalDetail) {
            $whatsappConsent = isset($quoteAdditionalDetail->flags['whatsapp_consent']) ? $quoteAdditionalDetail->flags['whatsapp_consent'] : false;
        }

        return $whatsappConsent;
    }
}

if (! function_exists('isNonSelfBillingEnabledForInsuranceProvider')) {
    function isNonSelfBillingEnabledForInsuranceProvider($insuranceProvider): bool
    {
        return $insuranceProvider?->non_self_billing == 1;
    }
}

if (! function_exists('getInsuranceProvider')) {
    function getInsuranceProvider($payment, $quoteType, $quote = null)
    {
        $insuranceProvider = null;
        $allowedQuoteTypes = [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value, QuoteTypes::TRAVEL->value, QuoteTypes::BIKE->value];

        //        Reminder:: Add Commercial vehicle logic for fetch correct provider
        if (ucfirst($quoteType) == QuoteTypes::CAR->value) {

            $quoteDetails = $payment->paymentable; // For Main Lead

            if (empty($quoteDetails) && isset($quote->personal_quote_id) && $quote?->personal_quote_id) { // For Endorsements
                $personalQuote = PersonalQuote::find($quote?->personal_quote_id);
                $quoteDetails = CarQuote::where('uuid', $personalQuote?->uuid)->first();
            }

            if (! empty($quoteDetails)) {
                $quoteDetails->fill(['full_name' => $quoteDetails->first_name.' '.$quoteDetails->last_name]);
                $isCommercialVehicle = app(\App\Services\LeadAllocationService::class)->isCommercialVehicles($quoteDetails);
                $vehicleType = \App\Models\VehicleType::find($quoteDetails?->vehicle_type_id)?->text;

                if ($isCommercialVehicle || ($quoteDetails?->source == \App\Enums\LeadSourceEnum::RENEWAL_UPLOAD && $vehicleType == strtoupper(QuoteTypes::BIKE->value))) {
                    return $payment?->insuranceProvider;
                }
            }
        }

        if (in_array(ucfirst($quoteType), $allowedQuoteTypes) && isset($payment)) {
            $planRelationName = strtolower($quoteType).'Plan';
            $payment->load($planRelationName);
            $insuranceProvider = $payment->$planRelationName?->insuranceProvider;
        }

        if (! $insuranceProvider) {
            $insuranceProvider = $payment?->insuranceProvider;
        }

        return $insuranceProvider;
    }
}

if (! function_exists('isCHSAdvisor')) {
    function isCHSAdvisor($userId)
    {
        $user = User::select('id')->chs()->first();

        return $user?->id == $userId;
    }
}

if (! function_exists('userHasProduct')) {
    function userHasProduct($product)
    {
        $productIds = auth()->user()->products->pluck('product_id');

        return Team::whereIn('id', $productIds)->where([['type', TeamTypeEnum::PRODUCT], ['is_active', 1], ['name', $product]])->exists();
    }
}
