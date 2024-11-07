<?php

namespace App\Services;

use App\Enums\DatabaseColumnsString;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\PetQuoteRequestDetail;
use App\Models\QuoteBatches;
use App\Traits\AddPremiumAllLobs;
use App\Traits\RolePermissionConditions;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PetQuoteService extends BaseService
{
    protected $query;

    use AddPremiumAllLobs;
    use RolePermissionConditions;

    protected $leadAllocationService;

    public function __construct(LeadAllocationService $leadAllocationService)
    {
        $this->leadAllocationService = $leadAllocationService;
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
                'pqr.policy_expiry_date',
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
                'pqr.has_injury',
                'pqr.customer_id',
                'pqr.parent_duplicate_quote_id',
                'pqr.renewal_import_code',
                'pqr.risk_score',
                'pqr.insly_migrated',
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
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'mobileNo' => $request->mobile_no,
            'gender' => $request->gender,
            'microchipNo' => $request->microchip_no,
            'typeOfPet1' => $request->type_of_pet1,
            'breedOfPet1' => $request->breed_of_pet1,
            'isMicrochipped' => $request->is_microchipped == 'Yes' ? true : false,
            'isNeutered' => $request->is_neutered == 'Yes' ? true : false,
            'isMixedBreed' => $request->is_mixed_breed == 'Yes' ? true : false,
            'anyInjury' => $request->any_injury == 'Yes' ? true : false,
            'ageOfPet1' => $request->age_of_pet1,
            'lang' => 'EN',
            'device' => 'DESKTOP',
            'utmSource' => '',
            'utmMedium' => '',
            'utmCampaign' => '',
            'iliveinAccommodationTypeId' => $request->ilivein_accommodation_type_id,
            'iamPossesionTypeId' => $request->iam_possesion_type_id,
            'source' => $sourceName,
            'referenceUrl' => $appUrl,
        ];
        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-pet-quote', $dataArr);

        if (isset($response->quoteUID)) {
            $this->savePremium(quoteTypeCode::PetQuote, $request, $response);
        }

        return $response;
    }

    public function getEntity($id)
    {
        return PetQuote::where('uuid', $id)->firstOrFail();
    }

    public function getEntityPlain($id)
    {
        return PetQuote::where('id', $id)->firstOrFail();
    }

    public function getSelectedLostReason($id)
    {
        $entity = PetQuoteRequestDetail::where('pet_quote_request_id', $id)->firstOrFail();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return PetQuoteRequestDetail::firstOrCreate(['pet_quote_request_id' => $id]);
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
        } elseif ($isNewManager || $isNewAdvisor) {
            $searchProperties = $model->newBusinessSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }
        if ($request->ajax()) {
            if (empty($request->email) && empty($request->code) && empty($request->first_name) &&
                empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)) {
                $this->query->where('pqr.quote_status_id', '!=', QuoteStatusEnum::Fake);
            }
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = $this->parseDate($request['assigned_to_date_start'], true);
                $dateTo = $this->parseDate($request['assigned_to_date_end'], false);
                $this->query->whereBetween('pqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = $this->parseDate($request['created_at'], true);
                $dateTo = $this->parseDate($request['created_at_end'], true);
                $this->query->whereBetween('pqr.created_at', [$dateFrom, $dateTo]);
            }

            if (Auth::user()->isSpecificTeamAdvisor('Pet')) {
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('pqr.advisor_id', Auth::user()->id); // fetch leads assigned to the user
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
                $this->query->where(function ($query) use ($request) {
                    $query->where('pqr.policy_number', $request->previous_quote_policy_number)
                        ->orWhere('pqr.previous_quote_policy_number', $request->previous_quote_policy_number);
                });
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
            $this->whereBasedOnRole($this->query, 'pqr');

            if (isset($request->is_renewal) && $request->is_renewal != '') {
                if ($request->is_renewal == quoteTypeCode::yesText) {
                    $this->query->whereNotNull('pqr.previous_quote_policy_number');
                }
                if ($request->is_renewal == quoteTypeCode::noText) {
                    $this->query->whereNull('pqr.previous_quote_policy_number');
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
                        $skipped = ['is_renewal', 'previous_policy_expiry_date'];
                        if (in_array($item, $skipped)) {
                            continue;
                        }
                        $this->query->where($this->getQuerySuffix($item).'.'.$item, $request[$item]);
                    }
                }
            }
        }

        // sortBy filter
        if (isset($request->sortBy) && $request->sortBy != '') {
            return $this->query->orderBy($request->sortBy, $request->sortType);
        } else {
            return $this->query->orderBy('pqr.created_at', 'DESC');
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
                return 'pqr';
                break;
        }
    }

    public function updatePetQuote(Request $request, $id)
    {
        $petQuote = PetQuote::where('uuid', $id)->first();
        $petQuote->first_name = $request->first_name;
        $petQuote->last_name = $request->last_name;
        $petQuote->gender = $request->gender;
        $petQuote->microchip_no = $request->microchip_no;
        $petQuote->type_of_pet1 = $request->type_of_pet1;
        $petQuote->breed_of_pet1 = $request->breed_of_pet1;
        $petQuote->is_microchipped = $request->is_microchipped == 'Yes' ? true : false;
        $petQuote->is_neutered = $request->is_neutered == 'Yes' ? true : false;
        $petQuote->is_mixed_breed = $request->is_mixed_breed == 'Yes' ? true : false;
        $petQuote->has_injury = $request->has_injury == 'Yes' ? true : false;
        $petQuote->age_of_pet1 = $request->age_of_pet1;
        $petQuote->ilivein_accommodation_type_id = $request->ilivein_accommodation_type_id;
        $petQuote->iam_possesion_type_id = $request->iam_possesion_type_id;
        $petQuote->save();

        if (isset($request->return_to_view)) {
            return redirect('quotes/pet')->with('success', 'Pet Quote has been updated');
        }
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
        if (! empty($CDBID)) {
            $query->where('pqr.id', '=', $CDBID);
        }
        if (! empty($email)) {
            $query->where('pqr.email', '=', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('pqr.mobile_no', '=', $mobile_no);
        }

        return $query;
    }

    public function updateChildRecord($id)
    {
        PetQuoteRequestDetail::updateOrCreate(
            ['pet_quote_request_id' => $id],
            [
                'advisor_assigned_date' => Carbon::now(),
                'advisor_assigned_by_id' => Auth::user()->id,
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
            'next_followup_date' => 'input|date|title|range',
            'source' => 'input|text',
            'lost_reason' => 'input|text',
            'premium' => 'input|number',
            'policy_number' => 'input|text',
            'type_of_pet1' => 'input|text|title|required|max:3',
            'breed_of_pet1' => 'input|text|title|required',
            'age_of_pet1' => 'input|number|title|required',
            'is_neutered' => 'static|Yes,No',
            'is_microchipped' => 'static|Yes,No',
            'microchip_no' => 'input|number',
            'is_mixed_breed' => 'static|Yes,No',
            'has_injury' => 'static|Yes,No',
            'gender' => 'static|Male,Female',
            'ilivein_accommodation_type_id' => 'select|title|required',
            'iam_possesion_type_id' => 'select|title|required',
            'previous_quote_id' => 'readonly|title',
            'is_renewal' => '|static|title|Yes,No',
            'policy_expiry_date' => 'input|date|title|range',
            'renewal_batch' => 'input|none',
            'previous_quote_policy_number' => 'input|title',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'previous_quote_policy_premium' => 'input|title',
            'parent_duplicate_quote_id' => 'input|title',
            'renewal_import_code' => 'input|text',
            'transapp_code' => 'readonly|none',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'code':
                $title = 'Ref-ID';
                break;
            case 'dob':
                $title = 'Date Of Birth';
                break;
            case 'mobile_no':
                $title = 'Mobile Number';
                break;
            case 'ilivein_accommodation_type_id':
                $title = 'Accommodation Type';
                break;
            case 'iam_possesion_type_id':
                $title = 'Possesion Type';
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
            case 'type':
                $title = 'Previous Policy Price';
                break;
            case 'type_of_pet1':
                $title = 'Type Of Pet';
                break;
            case 'breed_of_pet1':
                $title = 'Breed Of Pet';
                break;
            case 'age_of_pet1':
                $title = 'Age Of Pet';
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
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_import_code',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,next_followup_date,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info,renewal_import_code',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,device,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_import_code',
            'show' => 'source,is_renewal,previous_quote_id,quote_status_id,next_followup_date,transapp_code',
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
            'create' => 'premium,parent_duplicate_quote_id,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_import_code',
            'list' => 'parent_duplicate_quote_id,premium,policy_number,policy_expiry_date,is_renewal,email,mobile_no,others_info,dob,sum_insured_value,sum_insured_currency_id,purpose_of_insurance_id,marital_status_id,children_id,tenure_of_insurance_id,number_of_years_id,gender,is_smoker,others_info,next_followup_date,lost_reason,source,transapp_code,previous_quote_id,renewal_import_code',
            'update' => 'premium,parent_duplicate_quote_id,previous_quote_policy_premium,renewal_batch,previous_quote_policy_number,policy_expiry_date,is_renewal,previous_quote_id,id,advisor_id,quote_status_id,code,created_at,updated_at,next_followup_date,lost_reason,source,transapp_code,renewal_import_code',
            'show' => 'source,id,next_followup_date,lost_reason,is_renewal,previous_quote_id,quote_status_id,transapp_code',
        ];
    }

    public function fillNewBusinessProperties($model)
    {
        $model->newBusinessSearchProperties = ['created_at', 'code', 'first_name', 'last_name', 'email', 'mobile_no', 'policy_number'];
        $model->newBusinessSkipProperties = [
            'create' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code',
            'list' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,others_info,member_category_id,salary_band_id,gender,is_renewal,email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,has_dental,emirate_of_your_visa_id,is_ebp_renewal,health_team_type,next_followup_date,lost_reason,source,transapp_code,lead_type_id,policy_expiry_date,previous_quote_id,renewal_import_code',
            'update' => 'parent_duplicate_quote_id,previous_quote_policy_premium,previous_policy_expiry_date,renewal_batch,previous_quote_policy_number,member_category_id,salary_band_id,gender,is_renewal,previous_quote_id,created_at,updated_at,id,advisor_id,quote_status_id,code,health_team_type,next_followup_date,lost_reason,source,transapp_code,policy_expiry_date,renewal_import_code',
            'show' => 'source,member_category_id,salary_band_id,gender,is_renewal,id,next_followup_date,previous_quote_id,quote_status_id,transapp_code',
        ];
    }

    public function getDuplicateEntityByCode($code)
    {
        return PetQuote::where('parent_duplicate_quote_id', $code)->first();
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
            if (in_array(quoteTypeCode::Pet, newUi())) {
                $lead = PersonalQuote::findOrfail($leadId);
            } else {
                $lead = PetQuote::findOrfail($leadId);
            }

            if ($lead) {
                $lead->advisor_id = $userId;
                $lead->quote_batch_id = $quoteBatch->id;
                $lead->save();
                $this->updateChildRecord($lead->id);
            }
        }

        return $result;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return PetQuote::where('uuid', $uuid)->firstOrFail();
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
