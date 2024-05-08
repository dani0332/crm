<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Requests\AMLRequest;
use App\Models\AML;
use App\Models\ApplicationStorage;
use App\Models\BikeQuote;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteType;
use App\Models\CarQuote;
use App\Models\CommunicationMode;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\SanctionListDownloads;
use App\Models\TravelQuote;
use App\Models\UAEAMLListUploads;
use App\Models\YachtQuote;
use App\Services\AMLService;
use App\Services\QuoteStatusService;
use App\Services\SanctionListService;
use App\Traits\GenericQueriesAllLobs;
use Auth;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;

class AMLController extends Controller
{
    protected $quoteStatusService;
    protected $sanctionListService;
    use GenericQueriesAllLobs;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(QuoteStatusService $quoteStatusService, SanctionListService $sanctionListService)
    {
        $this->middleware('permission:aml-list', ['only' => ['index']]);
        $this->quoteStatusService = $quoteStatusService;
        $this->sanctionListService = $sanctionListService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AMLRequest $request)
    {
        $quoteTypes = QuoteType::withActive()->orderBy('sort_order')->get();
        $quoteStatuses = QuoteStatus::withActive()->orderBy('sort_order')->get();

        if ($request->ajax()) {
            if (isset($request->quoteType) && ! empty($request->quoteType)) {
                $quoteTypeCode = QuoteType::where('id', $request->quoteType)->value('code');
                $quoteRequestTable = strtolower($quoteTypeCode).'_quote_request';

                if (in_array($request->quoteType, [
                    QuoteTypes::BIKE->id(),
                    QuoteTypes::YACHT->id(),
                    QuoteTypes::PET->id(),
                    QuoteTypes::CYCLE->id(),
                    QuoteTypes::JETSKI->id(),
                ])) {
                    if (isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate)) {
                        $quoteRequestTable = app(AMLService::class)->isDataMigrated($request->quoteType, '', $request->amlCreatedStartDate) ? 'personal_quotes' : $quoteRequestTable;
                    } else {
                        if (isset($request->searchType) && in_array($request->searchType, ['cdbId', 'customerEmail', 'id'])) {
                            $searchType = match ($request->searchType) {
                                'cdbId' => 'code',
                                'customerEmail' => 'email',
                                'id' => 'id'
                            };

                            $createdDate =
                                $request->searchType == 'id' ? AML::where($searchType, $request->searchField)->firstOrFail()->created_at :
                                PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;

                            $quoteRequestTable = app(AMLService::class)->isDataMigrated($request->quoteType, '', $createdDate) ? 'personal_quotes' : strtolower($quoteTypeCode).'_quote_request';
                        }
                    }
                }

                $dataAml = AML::select('kyc_logs.id', 'kyc_logs.input', 'kyc_logs.screenshot', 'kyc_logs.created_at', 'kyc_logs.updated_at', 'kyc_logs.quote_request_id', 'kyc_logs.quote_type_id', 'quote_type.text as quote_type_text', $quoteRequestTable.'.code as cdb_id')
                    ->leftjoin('quote_type', 'quote_type.id', 'kyc_logs.quote_type_id')
                    ->leftjoin($quoteRequestTable, $quoteRequestTable.'.id', 'kyc_logs.quote_request_id')
                    ->where('kyc_logs.quote_type_id', $request->quoteType)
                    ->orderBy('kyc_logs.created_at', 'desc');

                $searchCriteriaSet = false;

                if (
                    isset($request->searchType) && ! empty($request->searchType) &&
                    isset($request->searchField) && ! empty($request->searchField)
                ) {
                    if ($request->searchType == 'cdbId') {
                        $dataAml->where($quoteRequestTable.'.code', $request->searchField);
                    }
                    if ($request->searchType == 'id') {
                        $dataAml->where('kyc_logs.id', $request->searchField);
                    }
                    if ($request->searchType == 'customerEmail') {
                        $dataAml->where($quoteRequestTable.'.email', $request->searchField);
                    }
                    $searchCriteriaSet = true;
                }
                if (isset($request->matchFound)) {
                    if ($request->matchFound == 'False') {
                        $dataAml->where('kyc_logs.results_found', '=', '0');
                    }
                    if ($request->matchFound == 'True') {
                        $dataAml->where('kyc_logs.results_found', '>', '0');
                    }
                }
                if (
                    isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate) &&
                    isset($request->amlCreatedEndDate) && ! empty($request->amlCreatedEndDate)
                ) {
                    $amlCreatedStartDate = Carbon::parse($request->amlCreatedStartDate)->startOfDay();
                    $amlCreatedEndDate = Carbon::parse($request->amlCreatedEndDate)->endOfDay();

                    $dataAml->whereBetween('kyc_logs.created_at', [$amlCreatedStartDate, $amlCreatedEndDate]);
                    $searchCriteriaSet = true;
                }

                $searchTypeIsValid = ($request->searchType == 'cdbId' || $request->searchType == 'customerEmail');
                $amlDateRangeNotProvided = isset($request->amlCreatedStartDate) && isset($request->amlCreatedEndDate);

                if (! $searchTypeIsValid && ! $amlDateRangeNotProvided) {
                    $dataAml->whereBetween('kyc_logs.created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()]);
                }

                if ($searchCriteriaSet) {
                    return DataTables::of($dataAml)
                        ->addIndexColumn()
                        ->addColumn('action', function ($row) {
                            return view('aml.actions', compact('row'))->render();
                        })
                        ->rawColumns(['action'])
                        ->make(true);
                }
            }

            return DataTables::of([])
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('aml.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('aml.view', compact('quoteTypes', 'quoteStatuses'));
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(AML $aml)
    {
        $amlResults = json_decode($aml->results);

        return view('aml.show', compact('aml', 'amlResults'));
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        $quoteType = QuoteType::where('id', '=', $quoteTypeId)->get(['code', 'text']);
        $quoteTypeCode = $quoteType[0]->code;
        $quoteTypeText = $quoteType[0]->text;
        $isCompanySearchEnabled = ApplicationStorage::where('key_name', '=', 'IS_AML_ENTITY_SEARCH_ENABLED')->value('value');
        if ($quoteTypeCode != '') {
            if ($quoteTypeCode == quoteTypeCode::Car) {
                $quoteRequest = CarQuote::select(
                    'car_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'uae_license_held_for.text as uae_license_text',
                    'car_make.text as car_make_text',
                    'car_model.text as car_model_text',
                    'emirates.text as emirates_text',
                    'car_type_insurance.text as car_type_ins_text',
                    'claim_history.text as claim_history_text',
                    'nationality.text as nationality_text'
                )
                    ->leftjoin('quote_status', 'car_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'car_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'car_quote_request.customer_id', 'customer.id')
                    ->leftjoin('uae_license_held_for', 'car_quote_request.uae_license_held_for_id', 'uae_license_held_for.id')
                    ->leftjoin('car_make', 'car_quote_request.car_make_id', 'car_make.id')
                    ->leftjoin('car_model', 'car_quote_request.car_model_id', 'car_model.id')
                    ->leftjoin('emirates', 'car_quote_request.emirate_of_registration_id', 'emirates.id')
                    ->leftjoin('car_type_insurance', 'car_quote_request.car_type_insurance_id', 'car_type_insurance.id')
                    ->leftjoin('claim_history', 'car_quote_request.claim_history_id', 'claim_history.id')
                    ->leftjoin('nationality', 'car_quote_request.nationality_id', 'nationality.id')
                    ->where('car_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'CarQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Health) {
                $quoteRequest = HealthQuote::select(
                    'health_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'health_cover_for.text as health_cover_text',
                    'marital_status.text as marital_status_text',
                    'emirates.text as emirates_text',
                    'nationality.text as nationality_text'
                )
                    ->leftjoin('quote_status', 'health_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'health_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'health_quote_request.customer_id', 'customer.id')
                    ->leftjoin('health_cover_for', 'health_quote_request.cover_for_id', 'health_cover_for.id')
                    ->leftjoin('marital_status', 'health_quote_request.marital_status_id', 'marital_status.id')
                    ->leftjoin('emirates', 'health_quote_request.emirate_of_your_visa_id', 'emirates.id')
                    ->leftjoin('nationality', 'health_quote_request.nationality_id', 'nationality.id')
                    ->where('health_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'HealthQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Home) {
                $quoteRequest = HomeQuote::select(
                    'home_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'home_possession_type.text as home_possession_type_text',
                    'home_accommodation_type.text as home_accommodation_type_text'
                )
                    ->leftjoin('quote_status', 'home_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'home_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'home_quote_request.customer_id', 'customer.id')
                    ->leftjoin('home_possession_type', 'home_quote_request.iam_possesion_type_id', 'home_possession_type.id')
                    ->leftjoin('home_accommodation_type', 'home_quote_request.ilivein_accommodation_type_id', 'home_accommodation_type.id')
                    ->where('home_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'HomeQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Travel) {
                $quoteRequest = TravelQuote::select(
                    'travel_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'region.text as region_cover_text',
                    'travel_cover_for.text as cover_for_text',
                    'nationality.text as nationality_text'
                )
                    ->leftjoin('quote_status', 'travel_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'travel_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'travel_quote_request.customer_id', 'customer.id')
                    ->leftjoin('region', 'travel_quote_request.region_cover_for_id', 'region.id')
                    ->leftjoin('travel_cover_for', 'travel_quote_request.travel_cover_for_id', 'travel_cover_for.id')
                    ->leftjoin('nationality', 'travel_quote_request.nationality_id', 'nationality.id')
                    ->where('travel_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'TravelQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Life) {
                $quoteRequest = LifeQuote::select(
                    'life_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'life_insurance_purpose.text as purpose_text',
                    'life_children.text as children_text',
                    'marital_status.text as marital_text',
                    'life_insurance_tenure.text as tenure_text',
                    'life_number_of_year.text as number_of_year_text',
                    'currency_type.text as currency_text',
                    'nationality.text as nationality_text'
                )
                    ->leftjoin('quote_status', 'life_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'life_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'life_quote_request.customer_id', 'customer.id')
                    ->leftjoin('life_insurance_purpose', 'life_quote_request.purpose_of_insurance_id', 'life_insurance_purpose.id')
                    ->leftjoin('life_children', 'life_quote_request.children_id', 'life_children.id')
                    ->leftjoin('marital_status', 'life_quote_request.marital_status_id', 'marital_status.id')
                    ->leftjoin('life_insurance_tenure', 'life_quote_request.tenure_of_insurance_id', 'life_insurance_tenure.id')
                    ->leftjoin('life_number_of_year', 'life_quote_request.number_of_years_id', 'life_number_of_year.id')
                    ->leftjoin('currency_type', 'life_quote_request.sum_insured_currency_id', 'currency_type.id')
                    ->leftjoin('nationality', 'life_quote_request.nationality_id', 'nationality.id')
                    ->where('life_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'LifeQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Bike) {
                $quoteRequest = BikeQuote::select(
                    'bike_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'nationality.text as nationality_text',
                    'uae_license_held_for.text as uae_license_text'
                )
                    ->leftjoin('quote_status', 'bike_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'bike_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'bike_quote_request.customer_id', 'customer.id')
                    ->leftjoin('nationality', 'bike_quote_request.nationality_id', 'nationality.id')
                    ->leftjoin('uae_license_held_for', 'bike_quote_request.uae_license_held_for_id', 'uae_license_held_for.id')
                    ->where('bike_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'BikeQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Yacht) {
                $quoteRequest = YachtQuote::select(
                    'yacht_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name'
                )
                    ->leftjoin('quote_status', 'yacht_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'yacht_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'yacht_quote_request.customer_id', 'customer.id')
                    ->where('yacht_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'YachtQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Business) {
                $quoteRequest = BusinessQuote::select(
                    'business_quote_request.*',
                    'quote_status.text as quote_status_text',
                    'payment_status.text as payment_status_text',
                    'customer.first_name as cust_f_name',
                    'customer.last_name as cust_l_name',
                    'business_type_of_insurance.text as business_type_text'
                )
                    ->leftjoin('quote_status', 'business_quote_request.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'business_quote_request.payment_status_id', 'payment_status.id')
                    ->leftjoin('customer', 'business_quote_request.customer_id', 'customer.id')
                    ->leftjoin('business_type_of_insurance', 'business_quote_request.business_type_of_insurance_id', 'business_type_of_insurance.id')
                    ->where('business_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = 'BusinessQuote';

                $businessTypeCode = BusinessQuoteType::where('id', '=', $quoteRequest->business_type_of_insurance_id)->value('code');
                $businessCoverTypeText = BusinessCoverType::where('id', '=', $quoteRequest->business_cover_type_id)->value('text');
                $businessCommuModeText = CommunicationMode::where('id', '=', $quoteRequest->communication_mode_id)->value('text');
            } elseif ($quoteTypeCode == quoteTypeCode::Pet) {
                if (app(AMLService::class)->isDataMigrated(QuoteTypes::PET->id(), $quoteRequestId)) {
                    $quoteRequest = PersonalQuote::byQuoteTypeId(QuoteTypes::PET->id())
                        ->select([
                            'personal_quotes.*',
                            'pet_quote_request.lang',
                            'pet_quote_request.reviver_name',
                            'pet_quote_request.promo_code',
                            'pet_quote_request.additional_notes',
                            'pet_quote_request.is_synced',
                            'pet_quote_request.previous_quote_id',
                            'pet_quote_request.quote_status_id',
                            'payment_status.text as payment_status_text',
                            'quote_status.text as quote_status_text',
                            'customer.first_name as cust_f_name',
                            'customer.last_name as cust_l_name',
                        ])
                        ->leftJoin('pet_quote_request', 'pet_quote_request.personal_quote_id', 'personal_quotes.id')
                        ->leftjoin('customer', 'customer.id', 'personal_quotes.customer_id')
                        ->leftjoin('quote_status', 'personal_quotes.quote_status_id', 'quote_status.id')
                        ->leftjoin('payment_status', 'personal_quotes.payment_status_id', 'payment_status.id')
                        ->where('personal_quotes.id', $quoteRequestId)
                        ->first();
                } else {
                    $quoteRequest = PetQuote::select([
                        'pet_quote_request.*',
                        'quote_status.text as quote_status_text',
                        'payment_status.text as payment_status_text',
                        'customer.first_name as cust_f_name',
                        'customer.last_name as cust_l_name',
                    ])
                        ->leftjoin('quote_status', 'pet_quote_request.quote_status_id', 'quote_status.id')
                        ->leftjoin('payment_status', 'pet_quote_request.payment_status_id', 'payment_status.id')
                        ->leftjoin('customer', 'pet_quote_request.customer_id', 'customer.id')
                        ->where('pet_quote_request.id', $quoteRequestId)->first();
                }
                $auditLogLine = 'PetQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Cycle) {
                $quoteRequest = PersonalQuote::byQuoteTypeId(QuoteTypes::CYCLE->id())
                    ->select([
                        'personal_quotes.*',
                        'cycle_quote_request.cycle_make',
                        'cycle_quote_request.cycle_model',
                        'cycle_quote_request.year_of_manufacture_id',
                        'cycle_quote_request.accessories',
                        'cycle_quote_request.has_accident',
                        'cycle_quote_request.has_good_condition',
                        'payment_status.text as payment_status_text',
                        'quote_status.text as quote_status_text',
                        'customer.first_name as cust_f_name',
                        'customer.last_name as cust_l_name',
                    ])
                    ->leftJoin('cycle_quote_request', 'cycle_quote_request.personal_quote_id', 'personal_quotes.id')
                    ->leftjoin('customer', 'customer.id', 'personal_quotes.customer_id')
                    ->leftjoin('quote_status', 'personal_quotes.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'personal_quotes.payment_status_id', 'payment_status.id')
                    ->where('personal_quotes.id', $quoteRequestId)
                    ->first();
                $auditLogLine = 'CycleQuote';
            } elseif ($quoteTypeCode == quoteTypeCode::Jetski) {
                $quoteRequest = PersonalQuote::byQuoteTypeId(QuoteTypes::JETSKI->id())
                    ->select([
                        'personal_quotes.*',
                        'jetski_quote_request.jetski_make',
                        'jetski_quote_request.jetski_model',
                        'jetski_quote_request.year_of_manufacture_id',
                        'jetski_quote_request.max_speed',
                        'jetski_quote_request.seat_capacity',
                        'jetski_quote_request.engine_power',
                        'jetski_quote_request.jetski_material_id',
                        'jetski_quote_request.jetski_use_id',
                        'jetski_quote_request.claim_history',
                        'payment_status.text as payment_status_text',
                        'quote_status.text as quote_status_text',
                        'customer.first_name as cust_f_name',
                        'customer.last_name as cust_l_name',
                    ])
                    ->leftJoin('jetski_quote_request', 'jetski_quote_request.personal_quote_id', 'personal_quotes.id')
                    ->leftjoin('customer', 'customer.id', 'personal_quotes.customer_id')
                    ->leftjoin('quote_status', 'personal_quotes.quote_status_id', 'quote_status.id')
                    ->leftjoin('payment_status', 'personal_quotes.payment_status_id', 'payment_status.id')
                    ->where('personal_quotes.id', $quoteRequestId)
                    ->first();
                $auditLogLine = 'JetskiQuote';
            } else {
                $quoteRequest = '';
            }
        } else {
            return redirect()->route('aml.details')->with('message', 'Not Found!');
        }

        if ($quoteRequest && $quoteRequest->quote_status_id && $quoteRequest->quote_status_id != '') {
            $quoteStatus = QuoteStatus::where('id', '=', $quoteRequest->quote_status_id)->get(['code']);
            $quoteStatusCode = $quoteStatus[0]->code;
        } else {
            $quoteStatusCode = '';
        }

        if (Auth::user()->hasRole(RolesEnum::COMPLIANCE)) {
            $isCurrentUserFromCompliance = 1;
        } else {
            $isCurrentUserFromCompliance = 0;
        }
        if (Auth::user()->hasRole(RolesEnum::PA) || Auth::user()->hasRole(RolesEnum::AML)) {
            $isCurrentUserFromPaAml = 1;
        } else {
            $isCurrentUserFromPaAml = 0;
        }

        $getFirstAmlLog = AML::where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteRequestId)->first();

        if ($getFirstAmlLog == null) {
            $firstAmlLogResults = 0;
        } else {
            $firstAmlLogResults = $getFirstAmlLog->results_found;
        }

        $getLatestAmlLog = AML::where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quoteRequestId)->latest()->first();
        if ($getLatestAmlLog == null) {
            $latestAmlLogResults = 0;
        } else {
            $latestAmlLogResults = $getLatestAmlLog->results_found;
        }

        $getAMLNumRows = AML::where('quote_type_id', '=', $quoteTypeId)
            ->where('quote_request_id', $quoteRequestId)->count();

        $nationalityList = $this->sanctionListService->fetchNationality();
        $yearsList = $this->sanctionListService->years();

        if ($quoteTypeCode == quoteTypeCode::Business) {
            return view('aml.details', compact(
                'quoteTypeCode',
                'quoteTypeText',
                'quoteRequest',
                'businessTypeCode',
                'businessCoverTypeText',
                'businessCommuModeText',
                'quoteStatusCode',
                'auditLogLine',
                'isCurrentUserFromCompliance',
                'isCurrentUserFromPaAml',
                'firstAmlLogResults',
                'latestAmlLogResults',
                'quoteTypeId',
                'getAMLNumRows',
                'nationalityList',
                'yearsList',
                'isCompanySearchEnabled'
            ));
        } else {
            return view('aml.details', compact(
                'quoteTypeCode',
                'quoteTypeText',
                'quoteRequest',
                'quoteStatusCode',
                'auditLogLine',
                'isCurrentUserFromCompliance',
                'isCurrentUserFromPaAml',
                'firstAmlLogResults',
                'latestAmlLogResults',
                'quoteTypeId',
                'getAMLNumRows',
                'nationalityList',
                'yearsList',
                'isCompanySearchEnabled'
            ));
        }
    }

    public function kycLogsRecords(Request $request)
    {
        $kycLogs = AML::where([
            'quote_request_id' => $request->quote_request_id,
            'quote_type_id' => $request->quote_type_id,
        ])->orderBy('created_at', 'desc');

        return DataTables::of($kycLogs)
            ->addIndexColumn()
            ->make(true);
    }

    public function quoteStatusUpdate($quoteTypeId, $quoteRequestId, $quoteStatusType)
    {
        $updateQuoteStatusResp = $this->quoteStatusService->updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType);

        if ($updateQuoteStatusResp == 'false') {
            return redirect()->back()->with('message', 'Quote Status is not updated');
        } else {
            $quoteStatusText = $updateQuoteStatusResp[0];
            $quoteCdbId = $updateQuoteStatusResp[1];
            $quoteTypeText = $updateQuoteStatusResp[2];
            $quotePaID = $updateQuoteStatusResp[3];
            $clientFullName = $updateQuoteStatusResp[4];
            if (Auth::user()->hasRole(RolesEnum::COMPLIANCE)) {
                app(AMLService::class)->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName);
            }

            return redirect()->back()->with('success', 'Quote Status is set to '.$quoteStatusText.'');
        }
    }

    public function quoteUpdate(Request $request, $quoteTypeId, $quoteRequestId)
    {
        $quoteId = $quoteRequestId;
        $this->validate($request, [
            'first_name' => 'required|max:200',
            'last_name' => 'required|max:200',
            'company_name' => 'max:300',
        ]);
        $quoteTypeCode = QuoteType::where('id', '=', $quoteTypeId)->value('code');
        if (checkPersonalQuotes($quoteTypeCode) && (! app(AMLService::class)->isDataMigrated($quoteTypeId, $quoteId))) {
            $quoteId = app(AMLService::class)->getPersonalQuoteId($quoteTypeId, $quoteId);
        }
        $updateQuote = $this->getQuoteObject($quoteTypeCode, $quoteId);

        if ($updateQuote) {
            $quoteUpdate = $updateQuote;
            $firstName = ucwords(strtolower($request->first_name));
            $lastName = ucwords(strtolower($request->last_name));
            $yob = $request->yob;
            $quoteUpdate->first_name = $firstName;
            $quoteUpdate->last_name = $lastName;
            // Check current user role is pa/AML > If yes > update pa_id - current_user_id
            if (Auth::user()->hasRole(RolesEnum::AML) || Auth::user()->hasRole(RolesEnum::PA)) {
                if (checkPersonalQuotes($quoteTypeCode)) {
                    app(AMLService::class)->updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, app(AMLService::class)->isDataMigrated($quoteTypeId, $quoteId));
                } else {
                    $quoteUpdate->pa_id = Auth::user()->id;
                }
            }
            $quoteUpdate->save();
        }

        if ($quoteTypeCode == quoteTypeCode::Business && $request->company_name != null) {
            $companyName = $request->company_name;
        } else {
            $companyName = null;
        }

        app(AMLService::class)->checkAml($firstName, $lastName, $quoteRequestId, $quoteTypeId, true, $yob, $companyName);

        return redirect()->back()->with('success', 'Quote is updated');
    }

    public function sanctionListHistory(Request $request, SanctionListDownloads $sanctionListDownloads, Datatables $datatables)
    {
        $url = env('AZURE_RYU_STORAGE_URL').env('AZURE_AML_HISTORY');

        if ($request->ajax()) {
            return $datatables::of($sanctionListDownloads::query()->orderBy('created_at', 'DESC'))
                ->addIndexColumn()
                ->make(true);
        }

        return view('aml.history', compact('url'));
    }

    public function uaeSanctionListUpload(Request $request)
    {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:application/vnd.ms-excel,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
        ]);

        $getUAEUploadRecord = UAEAMLListUploads::where('id', '=', 1)->get()->first();

        if ($getUAEUploadRecord == null) {
            $newUAEUploadRecord = new UAEAMLListUploads([
                'id' => 1,
                'file_name' => '16-11-2021_UAESanctionlist.xls',
                'is_updated' => false,
            ]);
            $newUAEUploadRecord->save();
        }

        $fileNameOriginal = $request->file_name->getClientOriginalName();
        $fileNameAzure = date('d-m-Y').'_'.$fileNameOriginal;
        $request->file('file_name')->storeAs('/', $fileNameAzure, 'azureForRyu');

        $newUpload = UAEAMLListUploads::where('id', '=', 1)->get()->first();
        $newUpload->file_name = $fileNameAzure;
        $newUpload->is_updated = true;
        $newUpload->save();

        return redirect('/kyc/aml/upload/uae')->with('success', 'UAE Sanction list uploaded successfully');
    }

    public function uploadUaeSanctionList()
    {
        return view('aml.upload');
    }
}
