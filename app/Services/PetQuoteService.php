<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\PetQuote;
use App\Models\PetQuoteRequestDetail;
use App\Models\QuoteStatus;
use Illuminate\Http\Request;
use DB;
use Auth;
use \Carbon\Carbon;
use Config;
use App\Traits\RolePermissionConditions;
use App\Traits\CustomerAdditionalInfo as CustomerAdditionalInfoTrait;
use App\Enums\quoteTypeCode;
use App\Enums\DatabaseColumnsString;
use App\Traits\AddPremiumAllLobs;
class PetQuoteService extends BaseService
{
    protected $query;
    use RolePermissionConditions;
    use CustomerAdditionalInfoTrait;
    use AddPremiumAllLobs;
    public function __construct()
    {
        $this->query = DB::table('pet_quote_request as pqr')
            ->select(
                'pqr.id',
                'pqr.uuid',
                'pqr.code',
                'pqr.updated_at',
                'pqr.created_at',
                'pqr.first_name',
                'pqr.last_name',
                'pqr.email',
                'pqr.mobile_no',
                'pqr.gender',
                'pqr.dob',
                'pqr.source',
                'pqr.premium',
                'pqr.policy_number',
                'pqr.quote_status_id',
                'qs.text as quote_status_id_text',
                'pqr.advisor_id',
                'u.name as advisor_id_text',
                'pqr.nationality_id',
                'n.TEXT AS nationality_id_text',
                'pqrd.next_followup_date',
                'pqrd.transapp_code',
                'pqrd.notes',
                'ls.text as lost_reason',
                'pqr.previous_quote_id',
                'pqr.renewal_batch',
                'pqr.previous_quote_policy_number',
                'pqr.renewal_expiry_date',
                'pqr.device',
                'pqr.previous_policy_expiry_date',
                'pqr.previous_quote_policy_premium',
                'pqr.microchip_no',
                'pqr.type_of_pet1',
                'pqr.age_of_pet1',
                'pqr.breed_of_pet1',
                'pqr.ilivein_accommodation_type_id',
                'hmt.text as ilivein_accommodation_type_id_text',
                'pqr.iam_possesion_type_id',
                'hpt.text as iam_possesion_type_id_text',
                'pqr.is_microchipped',
                'pqr.is_neutered',
                'pqr.is_mixed_breed',
                'pqr.has_injury'
            )
            ->leftJoin('pet_quote_request_detail as pqrd', 'pqrd.pet_quote_request_id', 'pqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'pqrd.lost_reason_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'pqr.quote_status_id')
            ->leftJoin('home_accommodation_type as hmt', 'hmt.id', '=', 'pqr.ilivein_accommodation_type_id')
            ->leftJoin('home_possession_type as hpt', 'hpt.id', '=', 'pqr.ilivein_accommodation_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'pqr.advisor_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'pqr.nationality_id');
    }
    public function savePetQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "mobileNo" => $request->mobile_no,
            "gender" => $request->gender,
            "microchipNo" => $request->microchip_no,
            "typeOfPet1" => $request->type_of_pet1,
            "breedOfPet1" => $request->breed_of_pet1,
            "isMicrochipped" => $request->is_microchipped == 'Yes' ?  true : false,
            "isNeutered" => $request->is_neutered == 'Yes' ?  true : false,
            "isMixedBreed" => $request->is_mixed_breed == 'Yes' ?  true : false,
            "anyInjury" => $request->any_injury == 'Yes' ?  true : false,
            "ageOfPet1" => $request->age_of_pet1,
            "lang" => "EN",
            "device" => "DESKTOP",
            "utmSource" => "",
            "utmMedium" => "",
            "utmCampaign" => "",
            "iliveinAccommodationTypeId" => $request->ilivein_accommodation_type_id,
            "iamPossesionTypeId" => $request->iam_possesion_type_id,
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-pet-quote', $dataArr);
        if(isset($response->quoteUID)){
            $this->savePremium(quoteTypeCode::PetQuote, $request, $response);
            return $this->createUpdateCustomerInfo($request, $request->email, $response->quoteUID, quoteTypeCode::PetQuote);
        }else {
            return $response;
        }
    }

    public function getEntity($id)
    {
        return $this->query->where('pqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return PetQuote::where('id', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = PetQuoteRequestDetail::where('pet_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = PetQuoteRequestDetail::where('pet_quote_request_id', $id)->first();
        if (!$entity) {
            $entity = $this->createDetailEntity($id);
        }
        return PetQuoteRequestDetail::where('pet_quote_request_id', $id)->first();
    }

    public function createDetailEntity($id)
    {
        return PetQuoteRequestDetail::create([
            'pet_quote_request_id' => $id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function getLeadsForAssignment()
    {
        return PetQuote::orderBy('created_at', 'desc')->get();
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
        }
        else if ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        if ($request->ajax()) {
            if (!isset($request->email) && $request->email == '') {
                $this->query->where('qs.id', '!=', 9);
            }
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('pqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('pqr.created_at', [$dateFrom, $dateTo]);
            }
            if(Auth::user()->isSpecificTeamAdvisor('Pet')){
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('pqr.advisor_id', Auth::user()->id);	// fetch leads assigned to the user
            }
            if (isset($request->code) && $request->code != '') {
                $this->query->where('pqr.code', $request->code);
            }
            if (isset($request->first_name) && $request->first_name != '') {
                $this->query->where('pqr.first_name', $request->first_name);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $this->query->where('pqr.last_name', $request->last_name);
            }
            if (isset($request->email) && $request->email != '') {
                $this->query->where('pqr.email', $request->email);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $this->query->where('pqr.mobile_no', $request->mobile_no);
            }
            if (isset($request->previous_quote_policy_number) && $request->previous_quote_policy_number != '') {
                $this->query->where('pqr.previous_quote_policy_number', $request->previous_quote_policy_number);
            }
            if (isset($request->renewal_batch) && $request->renewal_batch != '') {
                $this->query->where('pqr.renewal_batch', $request->renewal_batch);
            }
            if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('pqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
                $this->query->where('pqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
            }
            $this->whereBasedOnRole($this->query,'pqr');

            if (isset($request->is_renewal) && $request->is_renewal != '') {
                if($request->is_renewal == quoteTypeCode::yesText)
                    $this->query->whereNotNull('pqr.previous_quote_id');
                if($request->is_renewal == quoteTypeCode::noText)
                    $this->query->whereNull('pqr.previous_quote_id');
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else if ($item == 'advisor_id' && is_array($request[$item]) && !empty($request[$item])) {
                        if($request[$item][0] == 'null')
                            $this->query->whereNull('advisor_id');
                        else
                            $this->query->whereIn('advisor_id', $request[$item]);
                    }
                    else if ($item == DatabaseColumnsString::QUOTE_STATUS_ID && is_array($request[$item]) && !empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $skipped = array('is_renewal','previous_policy_expiry_date');
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
                    $column = "pqr.created_at";
                }
                if ($column == 7) {
                    $column = "pqr.updated_at";
                }
                if ($column == 8) {
                    $column = "pqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "pqr.created_at";
                }
                if ($column == 6) {
                    $column = "pqr.updated_at";
                }
                if ($column == 7) {
                    $column = "pqrd.next_followup_date";
                }
            }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('pqr.created_at', 'DESC');
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
                $title = "Previous Quote ID";
                break;
            default:
                return 'pqr';
                break;
        }
    }

    public function updatePetQuote(Request $request, $id)
    {
        $PetQuote = PetQuote::where('uuid', $id)->first();
        $PetQuote->first_name = $request->first_name;
        $PetQuote->last_name = $request->last_name;
        $PetQuote->email = $request->email;
        $PetQuote->mobile_no = $request->mobile_no;
        $PetQuote->gender = $request->gender;
        $PetQuote->microchip_no = $request->microchip_no;
        $PetQuote->type_of_pet1 = $request->type_of_pet1;
        $PetQuote->breed_of_pet1 = $request->breed_of_pet1;
        $PetQuote->is_microchipped = $request->is_microchipped == 'Yes' ?  true : false;
        $PetQuote->is_neutered = $request->is_neutered == 'Yes' ?  true : false;
        $PetQuote->is_mixed_breed = $request->is_mixed_breed == 'Yes' ?  true : false;
        $PetQuote->has_injury = $request->has_injury == 'Yes' ?  true : false;
        $PetQuote->age_of_pet1 = $request->age_of_pet1;
        $PetQuote->ilivein_accommodation_type_id = $request->ilivein_accommodation_type_id;
        $PetQuote->iam_possesion_type_id = $request->iam_possesion_type_id;
        $PetQuote->save();

        $this->createUpdateCustomerInfo($request, $PetQuote->email, $PetQuote->uuid, quoteTypeCode::PetQuote);
        if (isset($request->return_to_view))
            return redirect("quotes/pet")->with('success', 'Pet Quote has been updated');
    }

    public function getPetOverDueFollowups()
    {
        $query = DB::table('pet_quote_request as pqr')
            ->select(
                'pqr.id',
                'pqr.uuid',
                'pqr.code',
                DB::raw("CONCAT_WS(' ',pqr.first_name,pqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'pqr.created_at as createdAt',
                'pqr.quote_status_id',
                'pqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'pqr.updated_at',
                'pqr.source as leadSource',
                'pqr.premium',
                'pqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('pet_quote_request_detail as pqrd', 'pqrd.pet_quote_request_id', '=', 'pqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'pqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'pqrd.advisor_assigned_by_id')
            ->where('pqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->whereIn('qs.text', ['Followed Up','Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('pqr.advisor_id', Auth::user()->id);
            return $query;
    }

    public function getPetLeadsForAdvisor($request)
    {
        $query = DB::table('pet_quote_request as pqr')
            ->select(
                'pqr.id',
                'pqr.uuid',
                'pqr.code',
                'qs.text as leadStatus',
                'pqr.created_at as createdAt',
                'pqr.quote_status_id',
                'pqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'pqr.updated_at as updatedAt',
                'pqr.source as leadSource',
                'pqr.policy_number as policy_number',
                'pqr.email',
                'pqr.mobile_no',
                'pqrd.next_followup_date as nextFollowupDate',
                'pqr.previous_quote_id',
                'ps.text as paymentStatus',
                'pqr.renewal_batch as renewalBatch',
                'pqr.previous_quote_policy_number as previousPolicyNumber',
                DB::raw('DATE_FORMAT(pqr.previous_policy_expiry_date, "%d-%m-%Y") as previousPolicyExpiryDate'),
                'pqr.previous_quote_policy_premium as previousPolicyPremium',
                'pqr.first_name as firstName',
                'pqr.last_name as lastName',
            )
            ->leftJoin('pet_quote_request_detail as pqrd', 'pqrd.pet_quote_request_id', '=', 'pqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'pqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'pqrd.advisor_assigned_by_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'pqr.payment_status_id')
            ->where('qs.id', '!=', 9)
            ->where('pqr.advisor_id', Auth::user()->id);

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "pqr.created_at";
            }
            if ($column == 4) {
                $column = "pqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "pqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('pqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('pqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('pqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('pqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('pqr.quote_status_id', $request->leadStatus);
        }
        if (isset($request->paymentStatus)) {
            $query->where('pqr.payment_status_id', $request->paymentStatus);
        }
        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('pqr.previous_quote_id');
        }
        if (Auth::user()->isNewBusinessAdvisor()) {
            $query->whereNull('pqr.previous_quote_id');
        }
        if (isset($request->paymentStatus) && $request->paymentStatus != '') {
            $query->where('pqr.payment_status_id', $request->paymentStatus);
        }
        if (isset($request->renewal_batch) && $request->renewal_batch != '') {
            $query->where('pqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_number) && $request->previous_policy_number != '') {
            $query->where('pqr.previous_quote_policy_number', $request->previous_policy_number);
        }
        if (isset($request->previous_quote_policy_premium) && $request->previous_quote_policy_premium != '') {
            $query->where('pqr.previous_quote_policy_premium', $request->previous_quote_policy_premium);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $query->whereBetween('pqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        return $query;
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('pet_quote_request as pqr')
            ->select(
                'pqr.id',
                'pqr.uuid',
                'pqr.first_name',
                'pqr.last_name',
                'pqr.code',
                'pqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Pet' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status',
                'pqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('pet_quote_request_detail as pqrd', 'pqrd.pet_quote_request_id', '=', 'pqr.id')
            ->leftJoin('users as u', 'u.id', '=', 'pqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'pqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');
        if (!empty($CDBID)) {
            $query->where('pqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('pqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('pqr.mobile_no', '=', $mobile_no);
        }
        return $query;
    }

    public function updateChildRecord($id)
    {
        $childRecord = PetQuoteRequestDetail::where('pet_quote_request_id', $id)->first();

        if (empty($childRecord)) {
            $childRecord = $this->createDetailEntity($id);
        }

        $childRecord->advisor_assigned_by_id = Auth::user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();
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
            "source" => "input|text",
            "lost_reason" => "input|text",
            "premium" => "input|number",
            "policy_number" => "input|text",
            "type_of_pet1" => "input|text|title|required",
            "breed_of_pet1" => "input|text|title|required",
            "age_of_pet1" => "input|number|title|required",
            "is_neutered" => "static|Yes,No",
            "is_microchipped" => "static|Yes,No",
            "microchip_no" => "input|number",
            "is_mixed_breed" => "static|Yes,No",
            "has_injury" => "static|Yes,No",
            "gender" => "static|Male,Female",
            "ilivein_accommodation_type_id" => "select|title|required",
            "iam_possesion_type_id" => "select|title|required",
            "previous_quote_id" => "readonly|title",
            "is_renewal" => "|static|title|Yes,No",
            "renewal_expiry_date" => "input|date|title|range",
            "renewal_batch" => "input|none",
            "previous_quote_policy_number" => "input|title",
            "previous_policy_expiry_date" => "input|date|title|range",
            "previous_quote_policy_premium" => "input|title"
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'code':
                $title = "CDB ID";
                break;
            case 'dob':
                $title = "Date Of Birth";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'ilivein_accommodation_type_id':
                $title = "Accommodation Type";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'iam_possesion_type_id':
                $title = "Possesion Type";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'advisor_id':
                $title = "Assigned To";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'previous_quote_id':
                $title = "Previous Quote Id";
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
            case 'previous_quote_policy_premium':
                $title = "Previous Policy Premium";
                break;
            case 'type':
                $title = "Previous Policy Premium";
                break;
            case 'type_of_pet1':
                $title = "Type Of Pet";
                break;
            case 'breed_of_pet1':
                $title = "Breed Of Pet";
                break;
            case 'age_of_pet1':
                $title = "Age Of Pet";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code",
            "list" => "previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,next_followup_date,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info",
            "update" => "previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code",
            "show" => "is_renewal",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at','is_renewal'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'previous_quote_policy_number','previous_policy_expiry_date','renewal_batch','previous_quote_policy_premium'];
        $model->renewalSkipProperties = [
            "create" => "premium,device,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code",
            "list" => "device,premium,policy_number,renewal_expiry_date,is_renewal,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info,next_followup_date,lost_reason,source,transapp_code",
            "update" => "premium,device,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,renewal_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code",
            "show" => "premium,id,next_followup_date,lost_reason,is_renewal",
        ];
    }
    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            "create" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "list" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,others_info,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,renewal_expiry_date,previous_quote_id",
            "update" => "device,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,renewal_expiry_date",
            "show" => "previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id",
        ];
    }

    public function getDuplicateEntityByCode($code)
    {
        return PetQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function createDuplicate($parentRecord)
    {
        $quote = new PetQuote();
        $quote->parent_duplicate_quote_id = $parentRecord->code;
        $response = CapiRequestService::getUUID(QuoteTypeId::Pet);
        if($response) {
            $quote->uuid = $response->uuid;
            $quote->code = 'PET-'. $response->uuid;
        }
        $quote->quote_status_id = QuoteStatus::where('text', 'New Lead')->first()->id;
        $quote->first_name = $parentRecord->first_name;
        $quote->last_name = $parentRecord->last_name;
        $quote->email = $parentRecord->email;
        $quote->advisor_id = Auth::user()->id;
        $quote->mobile_no = $parentRecord->mobile_no;
        $quote->save();
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
            $query->where('a.auditable_type', 'App\Models\PetQuote')
            ->orWhere('a.auditable_type', 'App\Models\PetQuoteRequestDetail');
        })
        ->where(function ($query) {
            $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
        })
        ->where(function ($query) use ($id) {
            $detailObjId = PetQuoteRequestDetail::where('pet_quote_request_id', $id)->first();
            if($detailObjId) {
                $query->where('a.auditable_id', $id)
                ->orWhere('a.auditable_id', $detailObjId->id);
            } else {
                $query->where('a.auditable_id', $id);
            }
        })
        ->orderBy('a.created_at', 'DESC')->get();
        return $audits;
    }

}
