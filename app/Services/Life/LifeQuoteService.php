<?php

namespace App\Services\Life;

use App\Enums\DatabaseColumnsString;
use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteBatches;
use App\Traits\AddPremiumAllLobs;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Services\BaseService;
use App\Services\CapiRequestService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteLobs;
use App\Enums\quoteStatusCode;
use App\Services\Life\QuoteStatusService;

class LifeQuoteService extends BaseService
{
    protected $query;

    use AddPremiumAllLobs;
    use RolePermissionConditions;
    use GenericQueriesAllLobs;
    use PersonalQuoteLobs;

    public const TYPE = quoteTypeCode::Life;
    public const TYPE_ID = QuoteTypeId::Life;

    public function getData($forExport = false, $forTotalLeadsCount = false)
    {
        $quotes = $this->getQuotes($forExport, $forTotalLeadsCount);
        $quoteStatuses = $this->getPersonalQuoteStatuses(self::TYPE_ID)->get();
        $advisors = $this->getPersonalQuoteAdvisors(self::TYPE);
        $authorizedDays = $this->getPaymentAuthorisedDays();
        $renewalBatches = $this->getRenewalBaches();

        return compact('quotes', 'quoteStatuses', 'advisors', 'renewalBatches', 'authorizedDays');
    }

    private function getQuotes($forExport = false, $forTotalLeadsCount = false)
    {
        $query = $this->getQuery($forExport, $forTotalLeadsCount);

        return ($forExport) ? $query->get() : $query->simplePaginate(15)->withQueryString();
    }

    public function getQuery($forExport = false, $forTotalLeadsCount = false)
    {
        $query = PersonalQuote::byQuoteTypeCode(QuoteTypes::LIFE)->with([
            'advisor',
            'quoteStatus',
            'nationality',
            'quoteDetail.lostReason:id,text',
            'renewalBatchModel',
            'paymentStatus',
            'payments',
        ])
            ->when(\auth()->user()->hasRole(RolesEnum::LifeAdvisor), function ($query) {
                $query->where('advisor_id', \auth()->user()->id);
            })
            ->when(! empty(request()->advisor_assigned_date), function ($query) {
                $dateArray = request()->advisor_assigned_date;
                $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
                $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
                $query->whereHas('quoteDetail', function ($subQuery) use ($dateFrom, $dateTo) {
                    $subQuery->whereBetween('advisor_assigned_date', [$dateFrom, $dateTo]);
                });
            })
            ->filter(! $forExport, $forTotalLeadsCount)
            ->withFakeLeadCriteria($forTotalLeadsCount);

        $this->adjustQueryByInsurerInvoiceFilters($query);

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        $query->orderBy('personal_quotes.'.(request()->sortBy ?? 'created_at'), request()->sortType ?? 'desc');

        return $query;
    }

    public function getCardsViewData()
    {
        $quotes = [];

        $quoteStatuses = $this->getPersonalQuoteStatuses(self::TYPE_ID)
            ->whereIn('text', [quoteStatusCode::NEWLEAD, quoteStatusCode::QUOTED, quoteStatusCode::FOLLOWEDUP, quoteStatusCode::NEGOTIATION])
            ->get()->toArray();

        foreach ($quoteStatuses as &$quoteStatus) {
            $quotes[] = $this->getQuotesAgainstQuoteStatus($quoteStatus);
        }
        return [
            'quotes' => $quotes,
            'quoteType' => self::TYPE,
        ];
    }

    private function getQuotesAgainstQuoteStatus($quoteStatus)
    {
        $query = $this->getQuery();
        $query->where('quote_status_id', $quoteStatus['id']);
        $quoteStatus['data']['total_leads'] = $query->count();
        $quoteStatus['data']['total_premium'] = $query->sum('price_with_vat');
        $quoteStatus['data']['leads_list'] = $query->paginate(10);

        return $quoteStatus;
    }

    public function getCardsViewLoadMore($data)
    {
        $quoteStatus = [
            'id' => $data['status']
        ];
        return $this->getQuotesAgainstQuoteStatus($quoteStatus)['data'];
    }

    public function saveLifeQuote(Request $request)
    {
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
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
            'advisorId' => (! auth()->user()->hasRole(RolesEnum::Admin)) ? auth()->user()->id : null,
            'quoteTypeId' => intval(QuoteTypes::LIFE->id()),
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'createdById' => auth()->user()->id,
        ];

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-life-quote', $dataArr);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::LifeQuote, $request, $response);
        }

        return $response;
    }

    public function getEntity($id)
    {
        return $this->query->where('pq.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return PersonalQuote::where('id', $id)->with([
            'advisor',
            'quoteStatus',
            'nationality',
            'lifeQuote' => function ($q) {
                $q->with([
                    'children',
                    'currency',
                    'maritalStatus',
                    'purposeOfInsurance',
                    'insuranceTenure',
                    'numberOfYears',
                ]);
            },
            'quoteDetail.lostReason:id,text',
            'quoteDetail.previousAdvisor',
            'paymentStatus',
            'customer.additionalContactInfo',
            'transactionType',
            'insuranceProvider',
            'payments' => function ($q) {
                $q->with([
                    'paymentMethod',
                    'paymentStatus',
                    'paymentSplits' => function ($q) {
                        $q->with([
                            'paymentStatus',
                            'paymentMethod',
                            'documents',
                            'verifiedByUser',
                            'processJob',
                        ])->orderBy('sr_no', 'asc');
                    },
                ]);
            },
            'documents' => function ($q) {
                $q->with('createdBy')->orderBy('created_at', 'desc');
            },
            'quoteRequestEntityMapping' => function ($entityMapping) {
                $entityMapping->with('entity');
            },
        ])->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = PersonalQuoteDetail::where('personal_quote_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return PersonalQuoteDetail::firstOrCreate(
            ['personal_quote_id' => $id]
        );
    }

    public function getLeadsForAssignment()
    {
        return PersonalQuote::where('quote_type_id', QuoteTypeId::Life)->orderBy('created_at', 'desc')->get();
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

        if (empty($request->email) && empty($request->code) && empty($request->first_name) &&
                empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)) {
            $this->query->where('pq.quote_status_id', '!=', QuoteStatusEnum::Fake);
        }
        if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
            $dateFrom = $this->parseDate($request['assigned_to_date_start'], true);
            $dateTo = $this->parseDate($request['assigned_to_date_end'], false);
            $this->query->whereBetween('pqd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (! empty($request->created_at) && ! empty($request->created_at_end)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));
            $this->query->whereBetween('pq.created_at', [$dateFrom, $dateTo]);
        }
        if (isset($request->next_followup_date) && $request->next_followup_date != '') {
            $dateFrom = $this->parseDate($request['next_followup_date'], true);
            $dateTo = $this->parseDate($request['next_followup_date_end'], true);
            $this->query->whereBetween('pqd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (Auth::user()->isSpecificTeamAdvisor('Life')) {
            // if user has advisor Role then fetch leads assigned to the user only
            $this->query->where('pq.advisor_id', Auth::user()->id);    // fetch leads assigned to the user
        }
        if (isset($request->code) && $request->code != '') {
            $this->query->where('pq.code', $request->code);
        }
        if (isset($request->first_name) && $request->first_name != '') {
            $this->query->where('pq.first_name', $request->first_name);
        }
        if (isset($request->last_name) && $request->last_name != '') {
            $this->query->where('pq.last_name', $request->last_name);
        }
        if (isset($request->email) && $request->email != '') {
            $this->query->where('pq.email', $request->email);
        }
        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $this->query->where('pq.mobile_no', $request->mobile_no);
        }
        if (isset($request->policy_number) && $request->policy_number != '') {
            $this->query->where('pq.policy_number', $request->policy_number);
        }
        if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            $this->query->where('pq.previous_quote_policy_number', $request->previous_quote_policy_number);
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $this->query->where('pq.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('pq.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $this->query->where('pq.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }

        $this->whereBasedOnRole($this->query, 'pq');

        if (isset($request->is_renewal) && $request->is_renewal != '') {
            if ($request->is_renewal == GenericRequestEnum::Yes) {
                $this->query->whereNotNull('pq.previous_quote_policy_number');
            }
            if ($request->is_renewal == GenericRequestEnum::No) {
                $this->query->whereNull('pq.previous_quote_policy_number');
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

        if (isset($request->sortBy) && $request->sortBy != '') {
            return $this->query->orderBy($request->sortBy, $request->sortType);
        } else {
            return $this->query->orderBy('pq.created_at', 'DESC');
        }

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole('ADMIN');
            if ($isAdmin || $isManagerORDeputy == '1') {
                if ($column == 6) {
                    $column = 'pq.created_at';
                }
                if ($column == 7) {
                    $column = 'pq.updated_at';
                }
                if ($column == 8) {
                    $column = 'pqd.next_followup_date';
                }
            } else {
                if ($column == 5) {
                    $column = 'pq.created_at';
                }
                if ($column == 6) {
                    $column = 'pq.updated_at';
                }
                if ($column == 7) {
                    $column = 'pqd.next_followup_date';
                }
            }

            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('pq.created_at', 'DESC');
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
                return 'pq';
                break;
        }
    }

    public function updateLifeQuote(Request $request, $id)
    {
        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'mobile_no' => $request->mobile_no,
            'dob' => $request->dob,
            'sum_insured_value' => $request->sum_insured_value,
            'nationality_id' => $request->nationality_id,
            'sum_insured_currency_id' => $request->sum_insured_currency_id,
            'marital_status_id' => $request->marital_status_id,
            'purpose_of_insurance_id' => $request->purpose_of_insurance_id,
            'children_id' => $request->children_id,
            'premium' => $request->premium,
            'tenure_of_insurance_id' => $request->tenure_of_insurance_id,
            'number_of_years_id' => $request->number_of_years_id,
            'is_smoker' => $request->is_smoker,
            'gender' => $request->gender,
            'others_info' => $request->others_info,
        ];

        return DB::transaction(function () use ($id, $data) {
            $quote = PersonalQuote::where('uuid', $id)->first();

            //check the columns to be updated in personal quotes.
            $quoteData = Arr::only($data, (new PersonalQuote)->allowedColumns());
            $quoteData['updated_by_id'] = auth()->user()->id;
            $quote->update($quoteData);

            // check the columns to be updated in life quote request.
            if ($quote->lifeQuote) {
                $quote->lifeQuote()->update(Arr::only($data, (new LifeQuote)->allowedColumns()));
            } else {
                $quote->lifeQuote()->create(Arr::only($data, (new LifeQuote)->allowedColumns()));
            }

            return $quote;
        });
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('life_quote_request as pq')
            ->select(
                'pq.id',
                'pq.uuid',
                'pq.first_name',
                'pq.last_name',
                'pq.code',
                'pq.created_at',
                'u.name AS advisor_name',
                DB::raw("'Life' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status',
                'pqd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('personal_quote_details as pqd', 'pqd.personal_quote_id', '=', 'pq.id')
            ->leftJoin('users as u', 'u.id', '=', 'pq.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'pq.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (! empty($CDBID)) {
            $query->where('pq.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('pq.email', '=', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('pq.mobile_no', '=', $mobile_no);
        }

        return $query;
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
        return PersonalQuote::where('parent_duplicate_quote_id', $code)->first();
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

            $this->handleAssignment($lead, $userId, $quoteBatch, QuoteTypes::LIFE, PersonalQuoteDetail::class, 'personal_quote_id');
        }

        return $result;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return PersonalQuote::where('uuid', $uuid)->first();
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
}
