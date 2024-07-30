<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\IMCRMSearchTypesEnum;
use App\Enums\LookupsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\CustomerAdditionalInfo;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\HealthQuoteService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $client = new Hidehalo\Nanoid\Client();
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
    $modelType = (in_array(ucwords($modelType), newUi()) && checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

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

if (! function_exists('newUi')) {
    function newUi(): array
    {
        return [
            quoteTypeCode::Health,
            quoteTypeCode::Car,
            quoteTypeCode::Travel,
            quoteTypeCode::Home,
            quoteTypeCode::Life,
            quoteTypeCode::Pet,
            quoteTypeCode::CORPLINE,
            quoteTypeCode::Business,
            quoteTypeCode::Cycle,
            quoteTypeCode::Bike,
            quoteTypeCode::Yacht,
            quoteTypeCode::Jetski,
            quoteTypeCode::Aml,
        ];
    }
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
        return ['im.automation4@gmail.com', 'muhammad.abdullah@insurancemarket.ae'];
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
        $imLogo = 'images/im_logo_21k-hi.png';

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
        if ($modelType == HealthQuote::class && ! empty($request->assigned_to_date_start) && ! empty($request->assigned_to_date_end)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['assigned_to_date_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['assigned_to_date_end']));

            $partialQuery->whereHas('healthQuoteRequestDetail', function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
            });
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
    function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')->where('key_name', $keyName)->first();

        if (! $query) {
            return false;
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

if (! function_exists('getManagersByUser')) {
    function getManagersByUser($userId)
    {
        $managerIds = DB::table('user_manager')->where('user_id', $userId)->get()->pluck('manager_id');

        return User::whereIn('id', $managerIds)->where('is_active', 1)->get();
    }
}

if (! function_exists('roundNumber')) {
    function roundNumber($number)
    {
        return round($number, 2);
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
