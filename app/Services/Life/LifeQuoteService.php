<?php

namespace App\Services\Life;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\DocumentTypeCode;
use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentTooltip;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Facades\Capi;
use App\Models\CurrencyType;
use App\Models\LifeInsuranceTenure;
use App\Models\LifeNumberOfYears;
use App\Models\LifePurposeOfInsurance;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Enums\TravelQuoteEnum;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\Emirate;
use App\Models\LifeQuote;
use App\Models\LifeQuoteRequestDetail;
use App\Models\MartialStatus;
use App\Models\Nationality;
use App\Models\QuoteBatches;
use App\Repositories\ActivityRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LifeQuoteRepository;
use App\Repositories\LookupRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Repositories\UserRepository;
use App\Traits\AddPremiumAllLobs;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\BaseService;
use App\Services\CapiRequestService;
use App\Services\LeadAllocationService;
use App\Traits\CentralTrait;
use App\Services\CentralService;
use App\Services\CRUDService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;

class LifeQuoteService extends BaseService
{
    protected $query;

    use AddPremiumAllLobs, RolePermissionConditions, CentralTrait;

    protected $leadAllocationService;

    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->query = DB::table('life_quote_request as lqr')
            ->select(
                'lqr.id',
                'lqr.uuid',
                'lqr.code',
                DB::raw('DATE_FORMAT(lqr.created_at, "%d-%m-%y %H:%i") as created_at'),
                DB::raw('DATE_FORMAT(lqr.updated_at, "%d-%m-%y %H:%i") as updated_at'),
                'lqr.first_name',
                'lqr.last_name',
                'lqr.email',
                'lqr.mobile_no',
                'lqr.gender',
                DB::raw('DATE_FORMAT(lqr.dob, "%d-%m-%Y") as dob'),
                'lqr.is_smoker',
                'lqr.others_info',
                'lqr.sum_insured_value',
                'lqr.source',
                'lqr.premium',
                'lqr.policy_number',
                'lqr.sum_insured_currency_id',
                'ct.TEXT AS sum_insured_currency_id_text',
                'lqr.marital_status_id',
                'ms.TEXT AS marital_status_id_text',
                'lqr.purpose_of_insurance_id',
                'lip.TEXT AS purpose_of_insurance_id_text',
                'lqr.children_id',
                'lc.TEXT AS children_id_text',
                'lqr.tenure_of_insurance_id',
                'lit.TEXT AS tenure_of_insurance_id_text',
                'lqr.quote_status_id',
                'qs.text as quote_status_id_text',
                'lqr.advisor_id',
                'u.name as advisor_id_text',
                'lqr.number_of_years_id',
                'liy.TEXT AS number_of_years_id_text',
                'lqr.nationality_id',
                'n.TEXT AS nationality_id_text',
                DB::raw('DATE_FORMAT(lqrd.next_followup_date, "%d-%m-%Y") as next_followup_date'),
                'lqrd.transapp_code',
                'lqrd.notes',
                'ls.text as lost_reason',
                'lqr.previous_quote_id',
                'lqr.renewal_batch',
                'lqr.previous_quote_policy_number',
                'lqr.policy_expiry_date',
                'lqr.device',
                DB::raw('DATE_FORMAT(lqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
                'lqr.policy_start_date',
                'lqr.previous_quote_policy_premium',
                'lqr.customer_id',
                'lqr.parent_duplicate_quote_id',
                'lqr.risk_score',
                'lqr.kyc_decision',
                'lqr.insurance_provider_id',
                'ip.text AS insurance_provider_text',
                'lqr.insly_migrated',
                'lqr.policy_issuance_status_id',
                'lqr.insurer_quote_number',
                'lqr.policy_issuance_date',
                'lqrd.insly_id',
                'lu.text as transaction_type_text',
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', 'lqr.id')
            ->leftJoin('currency_type as ct', 'ct.id', '=', 'lqr.sum_insured_currency_id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'lqrd.lost_reason_id')
            ->leftJoin('marital_status as ms', 'ms.id', '=', 'lqr.marital_status_id')
            ->leftJoin('life_insurance_purpose as lip', 'lip.id', '=', 'lqr.purpose_of_insurance_id')
            ->leftJoin('life_children as lc', 'lc.id', '=', 'lqr.children_id')
            ->leftJoin('life_insurance_tenure as lit', 'lit.id', '=', 'lqr.tenure_of_insurance_id')
            ->leftJoin('life_number_of_year as liy', 'liy.id', '=', 'lqr.number_of_years_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'lqr.advisor_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'lqr.nationality_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'lqr.transaction_type_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'lqr.insurance_provider_id');
    }

    public function saveLifeQuote(Request $request)
    {
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'address' => $request->address,
            'mobileNo' => $request->mobile_no,
            'dob' => $request->dob,
            'sumInsuredValue' => $request->sum_insured_value,
            'nationalityId' => $request->nationality_id,
            'sumInsuredCurrencyId' => $request->sum_insured_currency_id,
            'maritalStatusId' => $request->marital_status_id,
            'purposeOfInsuranceId' => $request->purpose_of_insurance_id,
            'childrenId' => $request->children_id,
            'premium' => $request->premium,
            'tenureOfInsuranceId' => $request->tenure_of_insurance_id,
            'numberOfYearsId' => $request->number_of_years_id,
            'isSmoker' => $request->is_smoker == 1 ? 1 : 0,
            'gender' => $request->gender,
            'othersInfo' => $request->others_info,
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
        ];
        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $dataArr);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::LifeQuote, $request, $response);
        }

        return $response;
    }

    public function getEntity($id)
    {
        return $this->query->where('lqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return LifeQuote::where('id', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = LifeQuoteRequestDetail::where('life_quote_request_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return LifeQuoteRequestDetail::firstOrCreate(
            ['life_quote_request_id' => $id]
        );
    }

    public function getLeadsForAssignment()
    {
        return LifeQuote::orderBy('created_at', 'desc')->get();
    }

    public function getGridData($model, $request)
    {
        $searchProperties = [];
        $isRenewalUser = Auth::user()->isRenewalUser();
        $isRenewalAdvisor = Auth::user()->isRenewalAdvisor();
        $isRenewalManager = Auth::user()->isRenewalManager();
        $isNewManager = Auth::user()->isNewBusinessManager();
        $isNewAdvisor = Auth::user()->isNewBusinessAdvisor();
        if ($isRenewalUser || $isRenewalManager || $isRenewalAdvisor) {
            $searchProperties = $model->renewalSearchProperties;
        } elseif ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        // if ($request->ajax()) {
        if (empty($request->email) && empty($request->code) && empty($request->first_name) &&
                empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)) {
            $this->query->where('lqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
        }
        if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
            $dateFrom = $this->parseDate($request['assigned_to_date_start'], true);
            $dateTo = $this->parseDate($request['assigned_to_date_end'], false);
            $this->query->whereBetween('lqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (! empty($request->created_at) && ! empty($request->created_at_end)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));
            $this->query->whereBetween('lqr.created_at', [$dateFrom, $dateTo]);
        }
        if (isset($request->next_followup_date) && $request->next_followup_date != '') {
            $dateFrom = $this->parseDate($request['next_followup_date'], true);
            $dateTo = $this->parseDate($request['next_followup_date_end'], true);
            $this->query->whereBetween('lqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (Auth::user()->isSpecificTeamAdvisor('Life')) {
            // if user has advisor Role then fetch leads assigned to the user only
            $this->query->where('lqr.advisor_id', Auth::user()->id);    // fetch leads assigned to the user
        }
        if (isset($request->code) && $request->code != '') {
            $this->query->where('lqr.code', $request->code);
        }
        if (isset($request->first_name) && $request->first_name != '') {
            $this->query->where('lqr.first_name', $request->first_name);
        }
        if (isset($request->last_name) && $request->last_name != '') {
            $this->query->where('lqr.last_name', $request->last_name);
        }
        if (isset($request->email) && $request->email != '') {
            $this->query->where('lqr.email', $request->email);
        }
        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $this->query->where('lqr.mobile_no', $request->mobile_no);
        }
        if (isset($request->policy_number) && $request->policy_number != '') {
            $this->query->where('lqr.policy_number', $request->policy_number);
        }
        if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            $this->query->where('lqr.previous_quote_policy_number', $request->previous_quote_policy_number);
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $this->query->where('lqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('lqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $this->query->where('lqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }

        $this->whereBasedOnRole($this->query, 'lqr');

        if (isset($request->is_renewal) && $request->is_renewal != '') {
            if ($request->is_renewal == GenericRequestEnum::Yes) {
                $this->query->whereNotNull('lqr.previous_quote_policy_number');
            }
            if ($request->is_renewal == GenericRequestEnum::No) {
                $this->query->whereNull('lqr.previous_quote_policy_number');
            }
        }
        foreach ($searchProperties as $item) {
            if (! empty($request[$item]) && $item != 'created_at') {
                if ($request[$item] == 'null') {
                    $this->query->whereNull($item);
                } elseif ($item == 'advisor_id' && is_array($request[$item]) && ! empty($request[$item])) {
                    if ($request[$item][0] == 'null') {
                        $this->query->whereNull('advisor_id');
                    } else {
                        $this->query->whereIn('advisor_id', $request[$item]);
                    }
                } elseif ($item == DatabaseColumnsString::QUOTE_STATUS_ID && is_array($request[$item]) && ! empty($request[$item])) {
                    $this->query->whereIn('quote_status_id', $request[$item]);
                } else {
                    $skipped = ['is_renewal', 'previous_policy_expiry_date', 'next_followup_date'];
                    if (in_array($item, $skipped)) {
                        continue;
                    }
                    $this->query->where($this->getQuerySuffix($item).'.'.$item, $request[$item]);
                }
            }
        }
        // }

        if (isset($request->sortBy) && $request->sortBy != '') {
            return $this->query->orderBy($request->sortBy, $request->sortType);
        } else {
            return $this->query->orderBy('lqr.created_at', 'DESC');
        }

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole('ADMIN');
            if ($isAdmin || $isManagerORDeputy == '1') {
                if ($column == 6) {
                    $column = 'lqr.created_at';
                }
                if ($column == 7) {
                    $column = 'lqr.updated_at';
                }
                if ($column == 8) {
                    $column = 'lqrd.next_followup_date';
                }
            } else {
                if ($column == 5) {
                    $column = 'lqr.created_at';
                }
                if ($column == 6) {
                    $column = 'lqr.updated_at';
                }
                if ($column == 7) {
                    $column = 'lqrd.next_followup_date';
                }
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('lqr.created_at', 'DESC');
        }
    }

    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            if ($isStartOfDay) {
                return Carbon::createFromFormat($dateFormat, $date)->startOfDay()->toDateString();
            } else {
                return Carbon::createFromFormat($dateFormat, $date)->endOfDay()->toDateString();
            }
        }
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'sum_insured_currency_id':
                return 'ct';
                break;
            case 'marital_status_id':
                return 'ms';
                break;
            case 'nationality_id':
                return 'n';
                break;
            case 'purpose_of_insurance_id':
                return 'lip';
                break;
            case 'children_id':
                return 'lc';
                break;
            case 'tenure_of_insurance_id':
                return 'lit';
                break;
            case 'number_of_years_id':
                return 'liy';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote ID';
                break;
            default:
                return 'lqr';
                break;
        }
    }

    // NEED TO REMOVE AFTER CONFIRMATION
    // public function updateLifeQuote(Request $request, $id)
    // {
    //     $lifeQuote = LifeQuote::where('uuid', $id)->first();
    //     $lifeQuote->first_name = $request->first_name;
    //     $lifeQuote->last_name = $request->last_name;
    //     $lifeQuote->dob = $request->dob;
    //     $lifeQuote->gender = $request->gender;
    //     $lifeQuote->sum_insured_value = $request->sum_insured_value;
    //     $lifeQuote->sum_insured_currency_id = $request->sum_insured_currency_id;
    //     $lifeQuote->marital_status_id = $request->marital_status_id;
    //     $lifeQuote->nationality_id = $request->nationality_id;
    //     $lifeQuote->purpose_of_insurance_id = $request->purpose_of_insurance_id;
    //     $lifeQuote->children_id = $request->children_id;
    //     $lifeQuote->premium = $request->premium;
    //     $lifeQuote->tenure_of_insurance_id = $request->tenure_of_insurance_id;
    //     $lifeQuote->number_of_years_id = $request->number_of_years_id;
    //     $lifeQuote->others_info = $request->others_info;
    //     $lifeQuote->policy_start_date = $request->policy_start_date;
    //     $lifeQuote->is_smoker = $request->is_smoker == 1 ? 1 : 0;
    //     $lifeQuote->save();

    //     if (isset($request->return_to_view)) {
    //         return redirect('quotes/life')->with('success', 'Life Quote has been updated');
    //     }
    // }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('life_quote_request as lqr')
            ->select(
                'lqr.id',
                'lqr.uuid',
                'lqr.first_name',
                'lqr.last_name',
                'lqr.code',
                'lqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Life' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status',
                'lqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('life_quote_request_detail as lqrd', 'lqrd.life_quote_request_id', '=', 'lqr.id')
            ->leftJoin('users as u', 'u.id', '=', 'lqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'lqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (! empty($CDBID)) {
            $query->where('lqr.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('lqr.email', '=', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('lqr.mobile_no', '=', $mobile_no);
        }

        return $query;
    }

    public function updateChildRecord($id)
    {
        LifeQuoteRequestDetail::updateOrCreate(
            ['life_quote_request_id' => $id],
            [
                'advisor_assigned_date' => Carbon::now(),
                'advisor_assigned_by_id' => auth()->id(),
            ]
        );
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => 'input|title',
            'first_name' => 'input|text|required',
            'last_name' => 'input|text|required',
            'email' => 'input|email|required',
            'mobile_no' => 'input|title|number|required',
            'quote_status_id' => 'select|title|multiple',
            'advisor_id' => 'select|title|multiple',
            'created_at' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'dob' => 'input|date|title',
            'nationality_id' => 'select|title',
            'sum_insured_value' => 'input|number|title',
            'next_followup_date' => 'input|date|title|range',
            'transapp_code' => 'readonly|none',
            'source' => 'input|text',
            'lost_reason' => 'input|text',
            'premium' => 'input|number',
            'sum_insured_currency_id' => 'select|title',
            'purpose_of_insurance_id' => 'select|title',
            'marital_status_id' => 'select|title',
            'children_id' => 'select|title',
            'tenure_of_insurance_id' => 'select|title',
            'number_of_years_id' => 'select|title',
            'gender' => '|static|Male,Female',
            'is_smoker' => '|static|title|Yes,No',
            'others_info' => 'textarea',
            'previous_quote_id' => 'readonly|title',
            'is_renewal' => '|static|title|Yes,No',
            'policy_expiry_date' => 'input|date|title|range',
            'renewal_batch' => 'input|none',
            'previous_quote_policy_number' => 'input|title',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'previous_quote_policy_premium' => 'input|title',
            'parent_duplicate_quote_id' => 'input|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'code':
                $title = 'Ref-ID';
                break;
            case 'purpose_of_insurance_id':
                $title = 'Purpose of Insurance';
                break;
            case 'children_id':
                $title = 'Children';
                break;
            case 'tenure_of_insurance_id':
                $title = 'Type of Insurance';
                break;
            case 'number_of_years_id':
                $title = 'Tenure of Cover';
                break;
            case 'sum_insured_currency_id':
                $title = 'Currency';
                break;
            case 'dob':
                $title = 'Date Of Birth';
                break;
            case 'mobile_no':
                $title = 'Mobile Number';
                break;
            case 'is_smoker':
                $title = 'Smoker';
                break;
            case 'nationality_id':
                $title = 'Nationality';
                break;
            case 'sum_insured_value':
                $title = 'Sum Insured Value';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'advisor_id':
                $title = 'Advisor';
                break;
            case 'marital_status_id':
                $title = 'Marital Status';
                break;
            case 'next_followup_date':
                $title = 'Next Followup Date';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote Id';
                break;
            case 'policy_expiry_date':
                $title = 'Expiry Date';
                break;
            case 'previous_quote_policy_number':
                $title = 'Previous Policy Number';
                break;
            case 'previous_policy_expiry_date':
                $title = 'Previous Policy Expiry Date';
                break;
            case 'previous_quote_policy_premium':
                $title = 'Previous Policy Price';
                break;
            case 'parent_duplicate_quote_id':
                $title = 'Parent Ref-ID';
                break;
            default:
                break;
        }

        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'previous_quote_policy_premium,previous_policy_expiry_date,parent_duplicate_quote_id,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code',
            'list' => 'previous_quote_policy_premium,previous_policy_expiry_date,parent_duplicate_quote_id,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,next_followup_date,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info',
            'update' => 'previous_quote_policy_premium,previous_policy_expiry_date,parent_duplicate_quote_id,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code',
            'show' => 'is_renewal,previous_quote_id,quote_status_id',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'is_renewal', 'advisor_id'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'renewal_batch', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            'create' => 'parent_duplicate_quote_id,premium,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code',
            'list' => 'parent_duplicate_quote_id,premium,policy_number,policy_expiry_date,is_renewal,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info,next_followup_date,lost_reason,source,transapp_code',
            'update' => 'parent_duplicate_quote_id,premium,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code',
            'show' => 'premium,id,next_followup_date,lost_reason,is_renewal,previous_quote_id,quote_status_id',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,others_info,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,policy_expiry_date,previous_quote_id',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date',
            'show' => 'member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id',
        ];
    }

    public function getDuplicateEntityByCode($code)
    {
        return LifeQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function processManualLeadAssignment($request): array
    {
        if ($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadsIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        } else {
            $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }
        $userId = (int) $request->assigned_to_id_new;
        $quoteBatch = QuoteBatches::latest()->first();
        Log::info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);
        $result = [];
        foreach ($leadsIds as $leadId) {
            $lead = $this->getEntityPlain($leadId);
            $lead->advisor_id = $userId;
            $lead->quote_batch_id = $quoteBatch->id;
            $lead->save();
            $this->updateChildRecord($lead->id);
        }

        return $result;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return LifeQuote::where('uuid', $uuid)->first();
    }

    public function validateRequest($request)
    {
        $userId = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId == null || $request->selectTmLeadId == '' ? $request->entityId : $request->selectTmLeadId;
        if ($leadsIds == '' || $leadsIds == null) {
            return 'Please select lead(s) to assign';
        }
        if (substr($leadsIds, 0, 1) == ',') {
            $leadsIds = substr($leadsIds, 1);
        }
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $leadId) {
            $entity = $this->getEntityPlain($leadId);
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && auth()->user()->cannot(PermissionsEnum::ASSIGN_PAID_LEADS)) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }

        return 'true';
    }

    public function storeLifeQuote($data)
    {
        $lifeData = [
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'email' => $data['email'],
            'mobileNo' => $data['mobile_no'],
            'dob' => $data['dob'],
            'sumInsuredValue' => $data['sum_insured_value'],
            'nationalityId' => $data['nationality_id'],
            'sumInsuredCurrencyId' => $data['sum_insured_currency_id'],
            'maritalStatusId' => $data['marital_status_id'],
            'purposeOfInsuranceId' => $data['purpose_of_insurance_id'],
            'tenureOfInsuranceId' => $data['tenure_of_insurance_id'],
            'numberOfYearsId' => $data['number_of_years_id'],
            'isSmoker' => $data['is_smoker'] == 1 ? 1 : 0,
            'gender' => $data['gender'],
            'othersInfo' => $data['others_info'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'bmi' => $data['bmi'],
            'age' => $data['age'],
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
        ];

        $response = Capi::request('/api/v2-save-life-quote', 'post', $lifeData);

        if (isset($response->quoteUID)) {
            //todo: will remove this code once handled on Capi
            LifeQuote::where('uuid', $response->quoteUID)
                    ->update(['height' => $lifeData['height'], 
                            'weight' => $lifeData['weight'], 
                            'bmi' => $lifeData['bmi'], 
                            'age' => $lifeData['age']
                        ]);
        }

        return $response;
    }

    public function updateLifeQuote($uuid, $validatedData)
    {
        LifeQuote::where('uuid', $uuid)->update($validatedData);
    }

    public function getLifeQuoteData()
    {
        $query = LifeQuote::with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason',
            'renewalBatchModel', 'lifeQuoteRequestDetail', 'paymentStatus',
            'payments'])
            ->when(\auth()->user()->hasRole(RolesEnum::LifeAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->when(! empty(request()->advisor_assigned_date), function ($query) {
                $dateArray = request()->advisor_assigned_date;
                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                $query->whereHas('lifeQuoteRequestDetail', function ($subQuery) use ($dateFrom, $dateTo) {
                    $subQuery->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
                });
            })
            ->when(! empty(request()->tenure_of_insurance_id), function ($query) {
                $query->where('tenure_of_insurance_id', request()->tenure_of_insurance_id);
            })
            ->when(! empty(request()->number_of_years_id), function ($query) {
                $query->where('number_of_years_id', request()->number_of_years_id);
            })
            ->when(! empty(request()->sum_insured_range) && ! empty(request()->sum_insured_currency_id), function ($query) {
                $query->where('sum_insured_currency_id', request()->sum_insured_currency_id);
                    // ->whereBetween('sum_insured_value', request()->sum_insured_range);
                switch (request()->sum_insured_range) {
                    case 'lt500k':
                        $query->where('sum_insured_value', '<', 500000);
                        break;
                    case '500k-1m':
                        $query->whereBetween('sum_insured_value', [500000, 999999]);
                        break;
                    case 'gte1m':
                        $query->where('sum_insured_value', '>=', 1000000);
                        break;
                }
            })
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('life_quote_request.created_at', 'desc');

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'life_quote_request');

        return $query->simplePaginate()->withQueryString();
    }

    public function quoteExport()
    {
        return LifeQuote::with(['advisor', 'quoteStatus', 'nationality'])
            ->filter()
            ->withFakeLeadCriteria()
            ->orderBy('created_at', 'desc');
    }

    public function quoteExportData()
    {
        $query = LifeQuote::with(['advisor', 'quoteStatus', 'nationality', 'lifeQuoteRequestDetail.lostReason'])
            ->filter(false)
            ->withFakeLeadCriteria();
        $this->adjustQueryByDateFilters($query, 'life_quote_request');

        return $query->orderBy('life_quote_request.created_at', 'desc')
            ->get();
    }

    public function getFormOptions()
    {
        return [
            'nationalities' => Nationality::withActive()->get(),
            'currency' => CurrencyType::withActive()->get(),
            'purposeOfInsurance' => LifePurposeOfInsurance::withActive()->get(),
            'maritalStatus' => MartialStatus::withActive()->get(),
            'typeOfInsurance' => LifeInsuranceTenure::withActive()->orderBy('sort_order')->get(),
            'numberOfYears' => LifeNumberOfYears::withActive()->get(),

        ];
    }

    public function getQuoteByColumn($column, $value)
    {
        $quote = LifeQuote::where($column, $value)->with(['advisor', 'quoteStatus', 'nationality', 'previousAdvisor', 'lifeQuoteRequestDetail.lostReason',
            'purposeOfInsurance', 'children', 'currency', 'insuranceTenure', 'numberOfYears', 'maritalStatus',
            'paymentStatus', 'customer.additionalContactInfo', 'transactionType', 'insuranceProvider',
            'payments.paymentMethod', 'payments.paymentStatus', 'payments.paymentSplits.paymentStatus', 'payments.paymentSplits.paymentMethod', 'payments.paymentSplits.documents', 'payments.paymentSplits.verifiedByUser', 'payments.paymentSplits.processJob',
            'quoteRequestEntityMapping' => function ($entityMapping) {
                $entityMapping->with('entity');
            },
        ])
            ->with([
                'documents' => function ($q) {
                    $q->with('createdBy')->orderBy('created_at', 'desc');
                },
            ])
            ->with([
                'payments.paymentSplits' => function ($q) {
                    $q->orderBy('sr_no', 'asc');
                },
            ])
            ->select([
                'life_quote_request.*',
                'policy_expiry_date',
                'policy_start_date',
                'policy_issuance_date',
                \DB::raw('IF(EXISTS (
                    SELECT *
                    FROM quote_request_entity_mapping
                    WHERE quote_type_id = '.QuoteTypeId::Life.' AND quote_request_id = life_quote_request.id),
                    "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
                as customer_type'),
            ])->firstOrFail();

        $data = ! empty($quote) ? $quote->toArray() : [];
        $quote->lost_reason = $data['quote_detail']['lost_reason']['text'] ?? null;
        $quote->previous_advisor_id_text = $data['quote_detail']['previous_advisor']['name'] ?? null;
        $quote->transaction_type_text = $data['transaction_type']['text'] ?? null;

        return $quote;
    }
    public function getLifeQuoteShowData(LifeQuote $quote)
    {
        $payments = $quote?->payments;
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Life);
        $duplicateAllowedLobs = (new CentralService)->duplicateAllowedLobsList(QuoteTypes::LIFE->value, $quote->code);
        $linkedQuoteDetails = app(SendUpdateLogService::class)->linkedQuoteDetails(QuoteTypes::LIFE->value, $quote);

        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $membersDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::LIFE->name);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        $nationalities = NationalityRepository::withActive()->get();
        $embeddedProducts = EmbeddedProductRepository::byQuoteType(QuoteTypes::LIFE->id(), $quote->id);
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypes::LIFE->id());
        $activities = ActivityRepository::where([
            'quote_type_id' => QuoteTypes::LIFE->id(),
            'quote_request_id' => $quote->id,
        ])->with('assignee', 'quoteStatus')->orderBy('created_at', 'desc')->get();

        $uboDetails = CustomerMembersRepository::getBy($quote->id, QuoteTypes::LIFE->name, CustomerTypeEnum::Entity);
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $emirates = Emirate::where('is_active', 1)->select('id', 'text')->get();

        $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
            return ! in_array($value['id'], [QuoteStatusEnum::AMLScreeningCleared, QuoteStatusEnum::AMLScreeningFailed]);
        })->values();
        $quoteStatuses = app(CentralService::class)->lockTransactionStatus($quote, QuoteTypes::LIFE->id(), $quoteStatuses);

        if (! auth()->user()->can(PermissionsEnum::UPDATE_LEAD_STATUS_TO_FAKE_DUPLICATE)) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) {
                return ! in_array($value['id'], [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            })->values();
        }
        $sendUpdateOptions = [];
        $sendUpdateLogs = [];
        $sendUpdateEnum = (object) [];
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if ($hasPolicyIssuedStatus) {
            $sendUpdateOptions = (new LookupService)->getSendUpdateOptions(QuoteTypes::LIFE->id());
            $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);
            $sendUpdateEnum = SendUpdateLogStatusEnum::asArray();
        }

        $activitiesData = [];
        foreach ($activities as $activity) {
            $activitiesData[] = [
                'id' => $activity->id,
                'uuid' => $activity->uuid,
                'title' => $activity->title,
                'description' => $activity->description,
                'quote_request_id' => $activity->quote_request_id,
                'quote_type_id' => $activity->quote_type_id,
                'quote_uuid' => $activity->quote_uuid,
                'client_name' => $activity->client_name,
                'due_date' => $activity->due_date,
                'assignee' => $activity->assignee->name,
                'assignee_id' => $activity->assignee_id,
                'status' => $activity->status,
                'quote_status_id' => $activity->quote_status_id,
                'quote_status' => $activity?->quoteStatus,
                'user_id' => $activity?->user_id,
            ];
        }

        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
        @[$documentTypes, $paymentDocument] = app(QuoteDocumentService::class)->getDocumentTypes(QuoteTypeId::Life);

        $isQuoteDocumentEnabled = app(BaseService::class)->quoteDocumentEnabled(QuoteTypes::LIFE->value);
        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quote);
        $amlStatusName = AMLStatusCode::getName($quote->aml_status);
        $noteDocumentType = DocumentType::where('code', DocumentTypeCode::OD)->first();
        $quoteNotes = app(LifeQuoteNoteService::class)->getNotes($quote);
        $cdnPath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

        return [
            'documentTypes' => $documentTypes,
            'storageUrl' => storageUrl(),
            'quoteType' => QuoteTypes::LIFE,
            'quoteTypeId' => QuoteTypeId::Life,
            'quoteStatuses' => $quoteStatuses,
            'quote' => $quote,
            'amlStatusName' => $amlStatusName,
            'record' => $quote,
            'activities' => $activitiesData,
            'advisors' => $advisors,
            'allowedDuplicateLOB' => $duplicateAllowedLobs,
            'customerAdditionalContacts' => CustomerRepository::GetAdditionalContacts($quote->customer_id, $quote->mobile_no),
            'lostReasons' => $lostReasons,
            'modelType' => QuoteTypes::LIFE,
            'canAddBatchNumber' => auth()->user()->hasRole(RolesEnum::LifeManager),
            'embeddedProducts' => $embeddedProducts,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'nationalities' => $nationalities,
            'memberRelations' => $memberRelations,
            'membersDetails' => $membersDetails,
            'industryType' => $industryType,
            'emirates' => $emirates,
            'UBOsDetails' => $uboDetails,
            'UBORelations' => $uboRelations,
            'paymentMethods' => (new LookupService)->getPaymentMethods(),
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'payments' => $payments,
            'insuranceProviders' => $insuranceProviders,
            'permissions' => [
                'isQuoteDocumentEnabled' => $isQuoteDocumentEnabled,
            ],

            'enums' => [
                'travelQuoteEnum' => TravelQuoteEnum::asArray(),
            ],
            'vatPercentage' => $vatPercentage,
            'isNewPaymentStructure' => app(SplitPaymentService::class)->isNewPaymentStructure($quote->payments),
            'sendUpdateEnum' => $sendUpdateEnum,
            'sendUpdateOptions' => $sendUpdateOptions,
            'sendUpdateLogs' => $sendUpdateLogs,
            'hasPolicyIssuedStatus' => $hasPolicyIssuedStatus,
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'lockLeadSectionsDetails' => $lockLeadSectionsDetails,
            'paymentDocument' => $paymentDocument,
            'quoteNotes' => $quoteNotes,
            'noteDocumentType' => $noteDocumentType,
            'cdnPath' => $cdnPath,
        ];
    }
}
