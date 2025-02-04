<?php

namespace App\Services;

use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\QuoteBatches;
use App\Traits\AddPremiumAllLobs;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\RolePermissionConditions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeQuoteService extends BaseService
{
    protected $query;

    use AddPremiumAllLobs;
    use GenericQueriesAllLobs;
    use RolePermissionConditions;

    protected $leadAllocationService;

    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;

        $this->query = DB::table('home_quote_request as hqr')->select(
            'hqr.id',
            'hqr.code',
            'hqr.uuid',
            'hqr.first_name',
            'hqr.last_name',
            // 'hqr.email',
            // 'hqr.mobile_no',
            'hqr.company_name AS home_company_name',
            'hqr.company_address AS home_company_address',
            'hqr.address',
            'hqr.has_contents',
            'hqr.contents_aed',
            'hqr.has_personal_belongings',
            'hqr.personal_belongings_aed',
            'hqr.has_building',
            'hqr.building_aed',
            'hqr.source',
            'hqr.policy_number',
            'hqr.ilivein_accommodation_type_id',
            'hqr.quote_status_id',
            'hqr.additional_notes',
            'hqr.kyc_decision',
            'hqr.nationality_id',
            'hqr.risk_score',
            'hqr.insurance_provider_id',
            'hqr.insurer_quote_number',
            'hqr.price_vat_applicable',
            'hqr.price_vat_not_applicable',
            'hqr.price_with_vat',
            'hqr.stale_at',
            'qs.text as quote_status_id_text',
            DB::raw('DATE_FORMAT(hqr.created_at, "%d-%m-%y %H:%i") as created_at'),
            DB::raw('DATE_FORMAT(hqr.updated_at, "%d-%m-%y %H:%i") as updated_at'),
            DB::raw('DATE_FORMAT(hqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
            DB::raw('DATE_FORMAT(hqrd.next_followup_date, "%d-%m-%Y") as next_followup_date'),
            'hqr.premium',
            'hqr.advisor_id',
            'hqr.payment_status_id',
            'u.name as advisor_id_text',
            'hqr.previous_advisor_id',
            DB::raw('DATE_FORMAT(hqr.dob, "%d-%m-%Y") as dob'),
            'uadv.name AS previous_advisor_id_text',
            'hat.TEXT AS ilivein_accommodation_type_id_text',
            'hqr.iam_possesion_type_id',
            'hpt.TEXT AS iam_possesion_type_id_text',
            'n.TEXT AS nationality_id_text',
            'hqrd.transapp_code',
            'hqrd.notes',
            'hqrd.insly_id',
            'lu.text as transaction_type_text',
            'ls.text as lost_reason',
            'ls.id as lost_reason_id',
            'hqr.previous_quote_id',
            'hqr.policy_expiry_date',
            'hqr.renewal_batch',
            'rb.name as renewal_batch_text',
            'hqr.previous_quote_policy_number',
            'hqr.previous_quote_policy_premium',
            'hqr.customer_id',
            'hqr.parent_duplicate_quote_id',
            'hqr.renewal_import_code',
            DB::raw('IF(EXISTS (
                SELECT *
                FROM quote_request_entity_mapping
                WHERE quote_type_id = '.QuoteTypeId::Home.' AND quote_request_id = hqr.id),
                "'.CustomerTypeEnum::Entity.'", "'.CustomerTypeEnum::Individual.'")
            as customer_type'),
            'c.insured_first_name',
            'c.insured_last_name',
            'c.emirates_id_number',
            'c.emirates_id_expiry_date',
            'c.receive_marketing_updates',
            'qrem.entity_id',
            'ent.code as entity_code',
            'ent.trade_license_no',
            'ent.company_name',
            'ent.company_address',
            'qrem.entity_type_code',
            'ent.industry_type_code',
            'ent.emirate_of_registration_id',
            DB::raw('DATE_FORMAT(hqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as transaction_approved_at'),
            'hqr.price_vat_applicable',
            'hqr.vat',
            'hqr.insurer_quote_number',
            'hqr.policy_issuance_status_id',
            'hqr.policy_issuance_status_other',
            'hqr.policy_start_date',
            'hqr.policy_issuance_date',
            DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
            'ps.text AS payment_status_id_text',
            'hqr.policy_booking_date',
            'hqr.insly_migrated',
            'hqr.aml_status',
        )
            ->leftJoin('payments as py', 'py.code', '=', 'hqr.code')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'hqr.payment_status_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'hqr.nationality_id')
            ->leftJoin('home_quote_request_detail as hqrd', 'hqrd.home_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'hqr.transaction_type_id')
            ->leftJoin('users as uadv', 'uadv.id', '=', 'hqr.previous_advisor_id')
            ->leftJoin('home_accommodation_type as hat', 'hat.id', '=', 'hqr.ilivein_accommodation_type_id')
            ->leftJoin('home_possession_type as hpt', 'hpt.id', '=', 'hqr.iam_possesion_type_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->leftJoin('renewal_batches as rb', 'hqr.renewal_batch_id', '=', 'rb.id')
            ->leftJoin('customer as c', 'hqr.customer_id', 'c.id')
            ->leftJoin('quote_request_entity_mapping as qrem', function ($entityMappingJoin) {
                $entityMappingJoin->on('qrem.quote_type_id', '=', DB::raw(QuoteTypeId::Home));
                $entityMappingJoin->on('qrem.quote_request_id', '=', 'hqr.id');
            })
            ->leftJoin('entities as ent', 'qrem.entity_id', '=', 'ent.id');
    }

    public function getEntity($id)
    {
        return $this->query->addSelect(['hqr.email', 'hqr.mobile_no'])->where('hqr.uuid', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return HomeQuoteRequestDetail::firstOrCreate(
            ['home_quote_request_id' => $id],
        );
    }

    public function saveHomeQuote(Request $request)
    {
        $sourceName = config('constants.SOURCE_NAME');
        $appUrl = config('constants.APP_URL');
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'address' => $request->address,
            'mobileNo' => $request->mobile_no,
            'companyName' => $request->company_name,
            'companyAddress' => $request->company_address,
            'contentsAed' => $request->contents_aed,
            'premium' => $request->premium,
            'iamPossesionTypeId' => $request->iam_possesion_type_id,
            'iliveinAccommodationTypeId' => $request->ilivein_accommodation_type_id,
            'personalBelongingsAed' => $request->personal_belongings_aed,
            'policyNumber' => $request->policy_number,
            'buildingAed' => $request->building_aed,
            'hasContents' => $request->has_contents == 'on' ? true : false,
            'nationalityId' => $request->nationality_id,
            'hasBuilding' => $request->has_building == 'on' ? true : false,
            'hasPersonalBelongings' => $request->has_personal_belongings == 'on' ? true : false,
            'source' => $sourceName,
            'isPropertyRentedHolidayHome' => $request->is_property_rented_holiday_home == 'on' ? true : false,
            'referenceUrl' => $appUrl,
        ];
        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-home-quote', $dataArr);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::HomeQuote, $request, $response);
        }

        return $response;
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
        if (
            empty($request->email) && empty($request->code) && empty($request->first_name) &&
            empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)
        ) {
            $this->query->where('hqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
        }

        if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
            $dateFrom = $this->parseDate($request['created_at'], true);
            $dateTo = $this->parseDate($request['created_at_end'], true);
            $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
        }
        if (! empty($request->created_at_start) && ! empty($request->created_at_end) && empty($request->payment_due_date) && empty($request->booking_date) && ! isset($request->insurer_tax_invoice_number) && ! isset($request->insurer_commission_tax_invoice_number)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['created_at_start']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['created_at_end']));
            $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->advisor_assigned_date) && $request->advisor_assigned_date != '') {
            $dateArray = $request['advisor_assigned_date'];

            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }

        if (isset($request->policy_expiry_date) && $request->policy_expiry_date != '' && isset($request->policy_expiry_date_end) && $request->policy_expiry_date_end != '') {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['policy_expiry_date']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['policy_expiry_date_end']));
            $this->query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->next_followup_date) && $request->next_followup_date != '') {
            $dateFrom = $this->parseDate($request['next_followup_date'], true);
            $dateTo = $this->parseDate($request['next_followup_date_end'], true);
            $this->query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }

        if (isset($request->last_modified_date) && $request->last_modified_date != '') {
            $dateArray = $request['last_modified_date'];

            $dateFrom = Carbon::parse($dateArray[0])->startOfDay()->toDateTimeString();  // Start of the day for the first date
            $dateTo = Carbon::parse($dateArray[1])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('hqr.updated_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->code) && $request->code != '') {
            $this->query->where('hqr.code', $request->code);
        }
        if (isset($request->first_name) && $request->first_name != '') {
            $this->query->where('hqr.first_name', $request->first_name);
        }
        if (isset($request->last_name) && $request->last_name != '') {
            $this->query->where('hqr.last_name', $request->last_name);
        }
        if (isset($request->email) && $request->email != '') {
            $this->query->where('hqr.email', $request->email);
        }
        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $this->query->where('hqr.mobile_no', $request->mobile_no);
        }
        if (isset($request->policy_number) && $request->policy_number != '') {
            $this->query->where('hqr.policy_number', $request->policy_number);
        }
        if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
            $this->query->where(function ($query) use ($request) {
                $query->where('hqr.policy_number', $request->previous_quote_policy_number)
                    ->orWhere('hqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            });
        }
        if (isset($request->renewal_batches) && count($request->renewal_batches) > 0) {
            $this->query->whereIn('hqr.renewal_batch_id', $request->renewal_batches);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $this->query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $this->query->where('hqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (Auth::user()->isSpecificTeamAdvisor('Home')) {
            // if user has advisor Role then fetch leads assigned to the user only
            $this->query->where('hqr.advisor_id', Auth::user()->id); // fetch leads assigned to the user
        }

        // quote_status filter
        if (isset($request->quote_status) && $request->quote_status != '') {
            $this->query->whereIn('quote_status_id', $request->quote_status);
        }
        // advisors filter
        if (isset($request->advisors) && is_array($request->advisors) && count($request->advisors) > 0) {
            $this->query->whereIn('advisor_id', $request->advisors);
        }

        // payment_status_id filter
        if (isset($request->payment_status) && is_array($request->payment_status) && count($request->payment_status) > 0) {
            $this->query->whereIn('hqr.payment_status_id', $request->payment_status);
        }

        // is_cold filter
        if (isset($request->is_cold) && $request->is_cold != '') {
            $this->query->where('hqr.is_cold', 1);
        }

        // is_stale filter
        if (isset($request->is_stale) && $request->is_stale != '') {
            $this->query->whereNotNull('hqr.stale_at');
        }

        $this->whereBasedOnRole($this->query, 'hqr');

        if (isset($request->is_renewal) && $request->is_renewal != '') {
            if ($request->is_renewal == quoteTypeCode::yesText) {
                $this->query->whereNotNull('hqr.previous_quote_policy_number');
            }
            if ($request->is_renewal == quoteTypeCode::noText) {
                $this->query->whereNull('hqr.previous_quote_policy_number');
            }
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_TAX_INVOICE_NUMBER) && $request->has('insurer_tax_invoice_number')) {
            $this->query->where('py.insurer_tax_number', $request->insurer_tax_invoice_number);
        }

        if (auth()->user()->can(PermissionsEnum::SEARCH_INSURER_COMMISSION_TAX_INVOICE_NUMBER) && $request->has('insurer_commission_tax_invoice_number')) {
            $this->query->where('py.insurer_commmission_invoice_number', $request->insurer_commission_tax_invoice_number);
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

        $this->adjustQueryByDateFilters($this->query, 'hqr');

        // sortBy filter
        if (isset($request->sortBy) && $request->sortBy != '') {
            return $this->query->orderBy($request->sortBy, $request->sortType);
        } else {
            return $this->query->orderBy('hqr.created_at', 'DESC');
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
            case 'ilivein_accommodation_type':
                return 'hat';
                break;
            case 'iam_possesion_type':
                return 'hpt';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            default:
                return 'hqr';
                break;
        }
    }

    public function getLeadsForAssignment()
    {
        return HomeQuote::orderBy('created_at', 'desc')->get();
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('home_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.first_name',
                'hqr.last_name',
                'hqr.code',
                'hqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Home' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (! empty($CDBID)) {
            $query->where('hqr.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('hqr.email', '=', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('hqr.mobile_no', '=', $mobile_no);
        }

        return $query;
    }

    public function updateHomeQuote(Request $request, $id)
    {
        $homeQuote = HomeQuote::where('uuid', $id)->first();
        $homeQuote->first_name = $request->first_name;
        $homeQuote->last_name = $request->last_name;
        $homeQuote->address = $request->address;
        $homeQuote->company_name = $request->company_name;
        $homeQuote->company_address = $request->company_address;
        $homeQuote->contents_aed = $request->contents_aed;
        $homeQuote->iam_possesion_type_id = $request->iam_possesion_type_id;
        $homeQuote->ilivein_accommodation_type_id = $request->ilivein_accommodation_type_id;
        $homeQuote->personal_belongings_aed = $request->personal_belongings_aed;
        $homeQuote->building_aed = $request->building_aed;
        $homeQuote->has_contents = $request->has_contents == 'on' ? true : false;
        $homeQuote->nationality_id = $request->nationality_id;
        $homeQuote->premium = $request->premium;
        $homeQuote->policy_number = $request->policy_number;
        $homeQuote->has_building = $request->has_building == 'on' ? true : false;
        $homeQuote->has_personal_belongings = $request->has_personal_belongings == 'on' ? true : false;
        $homeQuote->save();

        if (isset($request->return_to_view)) {
            return redirect('quote/home/'.$id)->with('success', 'Home Quote has been updated');
        }
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
            'company_name' => 'input|text|max:250',
            'company_address' => 'input|text|max:1000',
            'quote_status_id' => 'select|title|multiple',
            'advisor_id' => 'select|title|multiple',
            'created_at' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'next_followup_date' => 'input|date|title|range',
            'transapp_code' => 'readonly|none',
            'source' => 'input|text|required',
            'lost_reason' => 'input|text',
            'premium' => 'input|number',
            'policy_number' => 'input|text',
            'contents_aed' => 'input|number|required',
            'personal_belongings_aed' => 'input|number|required',
            'building_aed' => 'input|number|required',
            'iam_possesion_type_id' => 'select|title|required',
            'ilivein_accommodation_type_id' => 'select|title|required',
            'has_contents' => 'input|checkbox|required',
            'has_personal_belongings' => 'input|checkbox|required',
            'has_building' => 'input|checkbox|required',
            'address' => 'textarea|required',
            'previous_quote_id' => 'readonly|title',
            'is_renewal' => '|static|Yes,No',
            'policy_expiry_date' => 'input|date|title|range',
            'renewal_batch' => 'select|title|multiple',
            'previous_quote_policy_number' => 'input|title',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'previous_quote_policy_premium' => 'input|number|title',
            'parent_duplicate_quote_id' => 'input|title',
            'additional_notes' => 'readonly',
            'renewal_import_code' => 'input|text',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'iam_possesion_type_id':
                $title = 'I am';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'created_at_end':
                $title = 'End Date';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'advisor_id':
                $title = 'Advisor';
                break;
            case 'ilivein_accommodation_type_id':
                $title = 'I Live In';
                break;
            case 'mobile_no':
                $title = 'Mobile Number';
                break;
            case 'code':
                $title = 'Ref-ID';
                break;
            case 'next_followup_date':
                $title = 'Next Followup Date';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote ID';
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
            case 'is_property_rented_holiday_home':
                $title = 'Is Property Rented Holiday Home ?';
                break;
            case 'previous_quote_policy_premium':
                $title = 'Previous Quote Price';
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
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,additional_notes',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,email,address,iam_possesion_type_id,next_followup_date,ilivein_accommodation_type_id,mobile_no,personal_belongings_aed,building_aed,contents_aed,has_contents,has_personal_belongings,has_building,address,policy_expiry_date,renewal_import_code,additional_notes',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code,additional_notes',
            'show' => 'source,is_renewal,next_followup_date,lost_reason,quote_status_id,previous_quote_id',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'is_renewal', 'advisor_id'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'renewal_batch', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,premium,source,transapp_code,policy_expiry_date,renewal_import_code',
            'list' => 'parent_duplicate_quote_id,policy_number,policy_expiry_date,is_renewal,email,address,iam_possesion_type_id,ilivein_accommodation_type_id,mobile_no,personal_belongings_aed,building_aed,contents_aed,has_contents,has_personal_belongings,has_building,address,next_followup_date,lost_reason,premium,source,transapp_code,policy_expiry_date,renewal_import_code',
            'update' => 'parent_duplicate_quote_id,premium,previous_quote_policy_premium,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code',
            'show' => 'source,premium,id,next_followup_date,is_renewal,previous_quote_id,quote_status_id',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,policy_expiry_date,previous_quote_id,renewal_import_code',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code',
            'show' => 'source,member_category_id,salary_band_id,gender,is_renewal,id,previous_quote_id,quote_status_id',
        ];
    }

    public function getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList)
    {
        $validationArray = [];
        $skipProperties = explode(',', $modelSkipPropertiesList);
        foreach ($modelPropertiesList as $propertyName => $propertyValue) {
            if (in_array($propertyName, $skipProperties)) {
                continue;
            }
            if ($propertyName == 'contents_aed' || $propertyName == 'personal_belongings_aed' || $propertyName == 'building_aed' || $propertyName == 'has_contents' || $propertyName == 'has_personal_belongings' || $propertyName == 'has_building') {
                if ($request['iam_possesion_type_id'] == null) {
                    $validationArray['has_contents'] = 'required';
                }
                if ($request['iam_possesion_type_id'] == '1') {
                    if ($request['has_building'] == null) {
                        $validationArray['has_contents'] = 'required';
                    }
                    if ($request['has_contents'] == null) {
                        $validationArray['has_building'] = 'required';
                    }
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }
                    if ($request['has_building'] == 'on') {
                        $validationArray['building_aed'] = 'required';
                    }
                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }

                if ($request['iam_possesion_type_id'] == '2') {
                    $validationArray['has_contents'] = 'required';
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }

                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }
            } else {
                if ($propertyName != 'id' && $propertyName != 'email' && $propertyName != 'code' && $propertyName != 'created_at' && $propertyName != 'updated_at' && $propertyName != 'mobile_no' && $propertyName != 'quote_status_id' && $propertyName != 'next_followup_date' && $propertyName != 'lost_reason' && $propertyName != 'source' && $propertyName != 'advisor_id' && $propertyName != 'policy_number' && $propertyName != 'previous_quote_policy_premium' && $propertyName != 'transapp_code' && $propertyName != 'premium' && $propertyName != 'previous_quote_id' && $propertyName != 'is_renewal' && $propertyName != 'policy_expiry_date' && $propertyName != 'renewal_batch' && $propertyName != 'previous_quote_policy_number' && $propertyName != 'previous_policy_expiry_date' && $propertyName != 'parent_duplicate_quote_id' && $propertyName !== 'renewal_import_code' && $propertyName !== 'additional_notes') {
                    $validationArray[$propertyName] = 'required';
                }
            }
        }

        return $validationArray;
    }

    public function getEntityPlain($id)
    {
        return HomeQuote::where('id', $id)->with([
            'insuranceProviderDetails',
            'payments' => function ($payment) {
                $payment->with([
                    'paymentSplits' => function ($paymentSplit) {
                        $paymentSplit->with([
                            'paymentStatus',
                            'paymentMethod',
                            'documents',
                            'verifiedByUser',
                            'processJob',
                        ]);
                        $paymentSplit->orderBy('sr_no');
                    },
                ]);
                $payment->orderBy('created_at');
            },
        ])->first();
    }

    public function getDuplicateEntityByCode($code)
    {
        return HomeQuote::where('parent_duplicate_quote_id', $code)->first();
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

            $this->handleAssignment($lead, $userId, $quoteBatch, QuoteTypes::HOME, HomeQuoteRequestDetail::class, 'home_quote_request_id');
        }

        return $result;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return HomeQuote::where('uuid', $uuid)->first();
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

    public function sendHomeOCB() {}
}
