<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use App\Models\QuoteStatus;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use \Carbon\Carbon;
use Config;
use App\Traits\RolePermissionConditions;
use App\Traits\CustomerAdditionalInfo as CustomerAdditionalInfoTrait;
use App\Enums\quoteTypeCode;
use App\Enums\DatabaseColumnsString;
use App\Enums\QuoteStatusEnum;
use Illuminate\Support\Facades\Log;

use App\Traits\AddPremiumAllLobs;
class HomeQuoteService extends BaseService
{
    protected $query;
    use RolePermissionConditions;
    use CustomerAdditionalInfoTrait;
    use AddPremiumAllLobs;
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
            'hqr.email',
            'hqr.mobile_no',
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
            'qs.text as quote_status_id_text',
            'hqr.created_at',
            'hqr.updated_at',
            'hqr.premium',
            'hqr.advisor_id',
            'u.name as advisor_id_text',
            'hat.TEXT AS ilivein_accommodation_type_id_text',
            'hqr.iam_possesion_type_id',
            'hpt.TEXT AS iam_possesion_type_id_text',
            'hqrd.next_followup_date',
            'hqrd.transapp_code',
            'hqrd.notes',
            'ls.text as lost_reason',
            'hqr.previous_quote_id',
            'hqr.renewal_expiry_date',
            'hqr.renewal_batch',
            'hqr.previous_quote_policy_number',
            'hqr.previous_policy_expiry_date',
            'hqr.previous_quote_policy_premium',
        )
            ->leftJoin('home_quote_request_detail as hqrd', 'hqrd.home_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('home_accommodation_type as hat', 'hat.id', '=', 'hqr.ilivein_accommodation_type_id')
            ->leftJoin('home_possession_type as hpt', 'hpt.id', '=', 'hqr.iam_possesion_type_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('hqr.uuid', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
        if (!$entity) {
            $entity = $this->createDetailEntity($id);
        }
        return $entity;
    }

    public function createDetailEntity($id)
    {
        return HomeQuoteRequestDetail::create([
            'home_quote_request_id' => $id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function saveHomeQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "contentsAed" => $request->contents_aed,
            "premium" => $request->premium,
            "iamPossesionTypeId" => $request->iam_possesion_type_id,
            "iliveinAccommodationTypeId" => $request->ilivein_accommodation_type_id,
            "personalBelongingsAed" => $request->personal_belongings_aed,
            "buildingAed" => $request->building_aed,
            "hasContents" => $request->has_contents == 'on' ?  true : false,
            "nationalityId" => $request->nationality_id,
            "hasBuilding" => $request->has_building == 'on' ? true : false,
            "hasPersonalBelongings" => $request->has_personal_belongings == 'on' ?  true : false,
            "source" => $sourceName,
            "isPropertyRentedHolidayHome" => $request->is_property_rented_holiday_home == 'on' ? true : false,
            "referenceUrl" => $appUrl,
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-home-quote', $dataArr);
        if(isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::HomeQuote, $request, $response);
            return $this->createUpdateCustomerInfo($request, $request->email, $response->quoteUID, quoteTypeCode::HomeQuote);
        }else {
            return $response;
        }
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
        } else if ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        if ($request->ajax()) {
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['next_followup_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['next_followup_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
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
                $this->query->where('hqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            }
            if (isset($request->renewal_batch) && $request->renewal_batch != '') {
                $this->query->where('hqr.renewal_batch', $request->renewal_batch);
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
                $this->query->where('hqr.advisor_id', Auth::user()->id);    // fetch leads assigned to the user
            }
            $this->whereBasedOnRole($this->query,'hqr');

            if (isset($request->is_renewal) && $request->is_renewal != '') {
                if ($request->is_renewal == quoteTypeCode::yesText)
                    $this->query->whereNotNull('hqr.previous_quote_id');
                if ($request->is_renewal == quoteTypeCode::noText)
                    $this->query->whereNull('hqr.previous_quote_id');
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else if ($item == 'advisor_id' && is_array($request[$item]) && !empty($request[$item])) {
                        if ($request[$item][0] == 'null')
                            $this->query->whereNull('advisor_id');
                        else
                            $this->query->whereIn('advisor_id', $request[$item]);
                    } else if ($item == DatabaseColumnsString::QUOTE_STATUS_ID && is_array($request[$item]) && !empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $skipped = array('is_renewal','previous_policy_expiry_date','next_followup_date');
                        if(in_array($item, $skipped)){
                            continue;
                        }
                        $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                    }
                }
            }
        }
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole("ADMIN");
            if ($isAdmin || $isManagerORDeputy == "1") {
                if ($column == 6) {
                    $column = "hqr.created_at";
                }
                if ($column == 7) {
                    $column = "hqr.updated_at";
                }
                if ($column == 8) {
                    $column = "hqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "hqr.created_at";
                }
                if ($column == 6) {
                    $column = "hqr.updated_at";
                }
                if ($column == 7) {
                    $column = "hqrd.next_followup_date";
                }
            }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('hqr.created_at', 'DESC');
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

    public function getHomeOverDueFollowups()
    {
        $query = DB::table('home_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.code',
                DB::raw("CONCAT_WS(' ',hqr.first_name,hqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'hqr.created_at as createdAt',
                'hqr.quote_status_id',
                'hqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'hqr.updated_at',
                'hqr.premium',
                'hqr.source as leadSource',
                'hqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('home_quote_request_detail as hqrd', 'hqrd.home_quote_request_id', '=', 'hqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqrd.advisor_assigned_by_id')
            ->where('hqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->whereIn('qs.text', ['Followed Up', 'Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('hqr.advisor_id', Auth::user()->id);
        return $query;
    }

    public function getHomeLeadsForAdvisor($request)
    {
        $query = DB::table('home_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.code',
                'qs.text as leadStatus',
                'hqr.created_at as createdAt',
                'hqr.updated_at as updatedAt',
                'hqr.quote_status_id',
                'hqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'hqr.policy_number as policy_number',
                'hqr.email',
                'hqr.mobile_no',
                'hqr.source as leadSource',
                'hqr.premium',
                'hqrd.next_followup_date as nextFollowupDate',
                'hqr.previous_quote_id',
                'ps.text as paymentStatus',
                'hqr.renewal_batch as renewalBatch',
                'hqr.previous_quote_policy_number as previousPolicyNumber',
                DB::raw('DATE_FORMAT(hqr.previous_policy_expiry_date, "%d-%m-%Y") as previousPolicyExpiryDate'),
                'hqr.previous_quote_policy_premium as previousPolicyPremium',
                'hqr.first_name as firstName',
                'hqr.last_name as lastName',
            )
            ->leftJoin('home_quote_request_detail as hqrd', 'hqrd.home_quote_request_id', '=', 'hqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqrd.advisor_assigned_by_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'hqr.payment_status_id')
            ->where('hqr.quote_status_id', '!=', 20)
            ->where('hqr.advisor_id', Auth::user()->id)
            ->orderBy('hqr.created_at', "DESC");

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "hqr.created_at";
            }
            if ($column == 4) {
                $column = "hqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "hqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('hqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('hqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('hqr.quote_status_id', $request->leadStatus);
        }
        if (isset($request->paymentStatus)) {
            $query->where('hqr.payment_status_id', $request->paymentStatus);
        }
        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('hqr.previous_quote_id');
        }
        if (Auth::user()->isNewBusinessAdvisor()) {
            $query->whereNull('hqr.previous_quote_id');
        }
        if (isset($request->paymentStatus) && $request->paymentStatus != '') {
            $query->where('hqr.payment_status_id', $request->paymentStatus);
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $query->where('hqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_number) && $request->previous_policy_number != '') {
            $query->where('hqr.previous_quote_policy_number', $request->previous_policy_number);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $query->where('hqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $query->whereBetween('hqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        return $query;
    }

    public function getLeadsForAssignment()
    {
        return HomeQuote::orderBy('created_at', 'desc')->get();
    }

    public function updateChildRecord($id)
    {
        $childRecord = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();

        if (empty($childRecord)) {
            $childRecord = $this->createDetailEntity($id);
        }

        $childRecord->advisor_assigned_by_id = Auth::user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();
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
        if (!empty($CDBID)) {
            $query->where('hqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('hqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
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
        $homeQuote->contents_aed = $request->contents_aed;
        $homeQuote->iam_possesion_type_id = $request->iam_possesion_type_id;
        $homeQuote->ilivein_accommodation_type_id = $request->ilivein_accommodation_type_id;
        $homeQuote->personal_belongings_aed = $request->personal_belongings_aed;
        $homeQuote->building_aed = $request->building_aed;
        $homeQuote->has_contents = $request->has_contents == 'on' ?  true : false;
        $homeQuote->nationality_id = $request->nationality_id;
        $homeQuote->premium = $request->premium;
        $homeQuote->has_building = $request->has_building == 'on' ? true : false;
        $homeQuote->has_personal_belongings = $request->has_personal_belongings == 'on' ?  true : false;
        $homeQuote->save();
        $this->createUpdateCustomerInfo($request, $homeQuote->email, $homeQuote->uuid, quoteTypeCode::HomeQuote);
        if (isset($request->return_to_view))
            return redirect("quote/home/" . $id)->with('success', 'Home Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "code" => "input|title",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "quote_status_id" => "select|title|multiple",
            "advisor_id" => "select|title|multiple",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "next_followup_date" => "input|date|title|range",
            "transapp_code" => "readonly|none",
            "source" => "input|text|required",
            "lost_reason" => "input|text",
            "premium" => "input|number",
            "policy_number" => "input|text",
            "contents_aed" => "input|number|required",
            "personal_belongings_aed" => "input|number|required",
            "building_aed" => "input|number|required",
            "iam_possesion_type_id" => "select|title|required",
            "ilivein_accommodation_type_id" => "select|title|required",
            "has_contents" => "input|checkbox|required",
            "has_personal_belongings" => "input|checkbox|required",
            "has_building" => "input|checkbox|required",
            // "is_property_rented_holiday_home" => "input|checkbox|required",
            "address" => 'textarea|required',
            "previous_quote_id" => "readonly|title",
            "is_renewal" => "|static|Yes,No",
            "renewal_expiry_date" => "input|date|title|range",
            "renewal_batch" => "input|none",
            "previous_quote_policy_number" => "input|title",
            "previous_policy_expiry_date" => "input|date|title|range",
            "previous_quote_policy_premium" =>  "input|number|title"
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'iam_possesion_type_id':
                $title = "I am";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'created_at_end':
                $title = "End Date";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'advisor_id':
                $title = "Assigned To";
                break;
            case 'ilivein_accommodation_type_id':
                $title = "I Live In";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'code':
                $title = "CDB ID";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'previous_quote_id':
                $title = "Previous Quote ID";
                break;
            case 'renewal_expiry_date':
                $title = "Expiry Date";
                break;
            case 'previous_quote_policy_number':
                $title = "Previous Policy Number";
                break;
            case 'previous_policy_expiry_date':
                $title = "Previous Policy Expiry Date";
                break;
            case 'is_property_rented_holiday_home':
                $title = "Is Property Rented Holiday Home ?";
                break;
            case 'previous_quote_policy_premium';
                $title = "Previous Quote Premium";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "list" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,email,address,iam_possesion_type_id,ilivein_accommodation_type_id,mobile_no,personal_belongings_aed,building_aed,contents_aed,has_contents,has_personal_belongings,has_building,address,renewal_expiry_date",
            "update" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "previous_quote_policy_premium,is_renewal,id,next_followup_date,lost_reason"
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'next_followup_date', 'is_renewal'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'renewal_batch', 'previous_quote_policy_number', 'previous_policy_expiry_date', 'previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            "create" => "previous_quote_policy_premium,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,premium,source,transapp_code,renewal_expiry_date",
            "list" => "policy_number,renewal_expiry_date,is_renewal,email,address,iam_possesion_type_id,ilivein_accommodation_type_id,mobile_no,personal_belongings_aed,building_aed,contents_aed,has_contents,has_personal_belongings,has_building,address,next_followup_date,lost_reason,premium,source,transapp_code,renewal_expiry_date",
            "update" => "premium,previous_quote_policy_premium,previous_policy_expiry_date,policy_number,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "premium,id,next_followup_date,is_renewal",
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            "create" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "list" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,renewal_expiry_date,previous_quote_id",
            "update" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,id,previous_quote_id",
        ];
    }

    public function getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList)
    {
        $validationArray = [];
        $skipProperties = explode(',', $modelSkipPropertiesList);
        foreach ($modelPropertiesList as $propertyName => $propertyValue) {
            if (in_array($propertyName, $skipProperties))
                continue;
            if ($propertyName == 'contents_aed' || $propertyName ==  'personal_belongings_aed' || $propertyName == 'building_aed' || $propertyName == 'has_contents' || $propertyName == 'has_personal_belongings' || $propertyName == 'has_building') {
                if ($request['iam_possesion_type_id'] == null) {
                    $validationArray['has_contents'] = 'required';
                }
                if ($request['iam_possesion_type_id'] == "1") {
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

                if ($request['iam_possesion_type_id'] == "2") {
                    $validationArray['has_contents'] = 'required';
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }

                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }
            } else {
                if ($propertyName != 'id' && $propertyName != 'email' && $propertyName != 'code'  && $propertyName != 'created_at' && $propertyName != 'updated_at' && $propertyName != 'mobile_no' && $propertyName != 'quote_status_id' && $propertyName != 'next_followup_date' && $propertyName != 'lost_reason' && $propertyName != 'source' && $propertyName != 'advisor_id' && $propertyName != 'policy_number' && $propertyName != 'previous_quote_policy_premium' && $propertyName != 'transapp_code' && $propertyName != 'premium' && $propertyName != 'previous_quote_id' && $propertyName != 'is_renewal' && $propertyName != 'renewal_expiry_date' && $propertyName != 'renewal_batch' && $propertyName != 'previous_quote_policy_number' && $propertyName != 'previous_policy_expiry_date') {
                    $validationArray[$propertyName] = 'required';
                }
            }
        }
        return $validationArray;
    }

    public function getEntityPlain($id)
    {
        return HomeQuote::where('id', $id)->first();
    }
    public function getDuplicateEntityByCode($code)
    {
        return HomeQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function getLeadAuditHistory($id)
    {
        $audits = DB::table('audits as a')
            ->select(
                'a.created_at as ModifiedAt',
                DB::raw('(SELECT name from users where id = a.user_id) as ModifiedBy'),
                DB::raw("(SELECT TEXT FROM quote_status WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.quote_status_id'))) AS NewStatus"),
                DB::raw("(SELECT NAME FROM users WHERE id = JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.advisor_id'))) AS NewAdvisor"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(a.new_values, '$.notes')) AS NewNotes")
            )
            ->where(function ($query) {
                $query->where('a.auditable_type', 'App\Models\HomeQuote')
                    ->orWhere('a.auditable_type', 'App\Models\HomeQuoteRequestDetail');
            })
            ->where(function ($query) {
                $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
                    ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
            })
            ->where(function ($query) use ($id) {
                $detailObjId = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
                if ($detailObjId) {
                    $query->where('a.auditable_id', $id)
                        ->orWhere('a.auditable_id', $detailObjId->id);
                } else {
                    $query->where('a.auditable_id', $id);
                }
            })
            ->orderBy('a.created_at', 'DESC')->get();
        return $audits;
    }

    public function createDuplicate($parentRecord)
    {
        $quote = new HomeQuote();
        $quote->parent_duplicate_quote_id = $parentRecord->code;
        $response = CapiRequestService::getUUID(QuoteTypeId::Home);
        if ($response) {
            $quote->uuid = $response->uuid;
            $quote->code = 'HOM-' . $response->uuid;
        }
        $quote->quote_status_id = QuoteStatusEnum::NewLead;
        $quote->first_name = $parentRecord->first_name;
        $quote->last_name = $parentRecord->last_name;
        $quote->email = $parentRecord->email;
        $quote->advisor_id = Auth::user()->id;
        $quote->mobile_no = $parentRecord->mobile_no;
        $quote->save();
    }

    public function processManualLeadAssignment($request): array
    {
        $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        $userId = (int)$request->assigned_to_id_new;
        Log::info('Leads ids to assign: ' . json_encode($leadsIds));
        $result = [];
        foreach($leadsIds as $leadId)
        {
            $lead = $this->getEntityPlain($leadId);
            $lead->advisor_id = $userId;
            $lead->save();
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
        $leadsIds = $request->selectTmLeadId;
        if ($leadsIds == '' || $leadsIds == null) {
            return 'Please select lead(s) to assign';
        }
        if (substr($leadsIds, 0, 1) == ',') {
            $leadsIds = substr($leadsIds, 1);
        }
        $leadsIds = array_map('intval', explode(',', $leadsIds));
        foreach ($leadsIds as $leadId) {
            $entity = $this->getEntityPlain($leadId);
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }
        return 'true';
    }

}
