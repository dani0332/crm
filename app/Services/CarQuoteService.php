<?php

namespace App\Services;

use App\Enums\QuoteTypeId;
use App\Models\CarMake;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\InsuranceProvider;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\YearOfManufacture;
use Illuminate\Http\Request;
use Config;
use DB;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Enums\quoteStatusCode;
class CarQuoteService extends BaseService
{
    protected $query;
    protected $httpService;
    protected $childUserIds = [];
    public function __construct(HttpRequestService $httpService)
    {
        $this->httpService = $httpService;
        $this->query = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.uuid',
                'cqr.id',
                'cqr.first_name',
                'cqr.last_name',
                'cqr.email',
                'cqr.mobile_no',
                'cqr.dob',
                'cqr.car_value',
                'cqr.additional_notes',
                'cqr.nationality_id',
                'cqr.year_of_manufacture',
                'cqr.code',
                'cqr.is_ecommerce',
                'cqr.premium',
                'cqr.paid_at',
                'cqr.payment_gateway',
                'cqr.source',
                'cqr.created_at',
                'cqr.updated_at',
                'cqr.seat_capacity',
                'cqr.cylinder',
                'cqr.vehicle_type_id',
                'n.TEXT AS nationality_id_text',
                'cqr.promo_code',
                'cqr.device',
                'cqr.policy_number',
                'cqr.previous_quote_id',
                'cqr.renewal_batch',
                'cqr.renewal_expiry_date',
                'cqr.order_reference',
                'cqr.payment_reference',
                'cqr.calculated_value',
                'cqr.created_by',
                'cqr.updated_by',
                'cqr.uae_license_held_for_id',
                'ulhf.TEXT AS uae_license_held_for_id_text',
                'cqr.car_make_id',
                'cmake.TEXT AS car_make_id_text',
                'cqr.car_model_id',
                'cmodel.TEXT AS car_model_id_text',
                'cqr.emirate_of_registration_id',
                'e.TEXT AS emirate_of_registration_id_text',
                'cqr.car_type_insurance_id',
                'cti.TEXT AS car_type_insurance_id_text',
                'cqr.claim_history_id',
                'ch.TEXT AS claim_history_id_text',
                'cqr.advisor_id',
                'u.name AS advisor_id_text',
                'cqr.payment_status_id',
                'ps.text AS payment_status_id_text',
                'cqr.plan_id',
                'cp.text AS plan_id_text',
                'cp.provider_id AS car_plan_provider_id',
                'cpip.text AS car_plan_provider_id_text',
                'cqr.quote_status_id',
                'qs.text AS quote_status_id_text',
                'cqr.year_of_manufacture AS year_of_manufacture_text',
                'cqrd.next_followup_date',
                'cqrd.transapp_code',
                'cqrd.notes',
                'vt.text as vehicle_type_id_text',
                'cqr.currently_insured_with',
                'cqr.currently_insured_with as currently_insured_with_text',
                'ls.text as lost_reason',
                'cqr.previous_quote_policy_number',
                'cqr.previous_policy_expiry_date',
                'cqr.previous_quote_policy_premium',
                'cqr.is_modified',
                'cqr.is_bank_financed',
                'cqr.is_gcc_standard',
                'cqr.current_insurance_status',
                'cqr.year_of_first_registration'
            )
            ->leftJoin('nationality as n', 'n.id', '=', 'cqr.nationality_id')
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'cqrd.lost_reason_id')
            ->leftJoin('car_make as cmake', 'cmake.id', '=', 'cqr.car_make_id')
            ->leftJoin('uae_license_held_for as ulhf', 'ulhf.id', '=', 'cqr.uae_license_held_for_id')
            ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'cqr.car_model_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'cqr.emirate_of_registration_id')
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
            ->leftJoin('claim_history as ch', 'ch.id', '=', 'cqr.claim_history_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('vehicle_type as vt', 'vt.id', '=', 'cqr.vehicle_type_id');
    }

    public function saveCarQuote(Request $request)
    {
        $yearOfManufactureText = YearOfManufacture::where('id', '=', $request->year_of_manufacture)->value('text');
        $insuranceProviderText = InsuranceProvider::where('id', '=', $request->currently_insured_with)->value('text');
        $carMakeId = CarMake::where('code', '=', $request->car_make_id)->value('id');
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');

        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "dob" => $request->dob,
            "nationalityId" => $request->nationality_id,
            "uaeLicenseHeldForId" => $request->uae_license_held_for_id,
            "yearOfManufacture" => $yearOfManufactureText, // TEXT
            "emirateOfRegistrationId" => $request->emirate_of_registration_id,
            "carTypeInsuranceId" => $request->car_type_insurance_id,
            "claimHistoryId" => $request->claim_history_id,
            "additionalNotes" => $request->additional_notes,
            "carValue" => $request->car_value,
            "seatCapacity" => $request->seat_capacity,
            "cylinder" => $request->cylinder,
            "vehicleTypeId" => $request->vehicle_type_id,
            "premium" => $request->premium,
            "carMakeId" => $carMakeId, // ID
            "carModelId" => $request->car_model_id, // ID
            "currentlyInsuredWith" => $insuranceProviderText, // TEXT
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
        );
        if (!Auth::user()->hasRole("ADMIN")) $dataArr['advisorId'] = Auth::user()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-car-quote', $dataArr);
    }

    public function updateCarQuote(Request $request, $id)
    {
        $carQuote = CarQuote::where('uuid', $id)->first();
        $carQuote->first_name = $request->first_name;
        $carQuote->last_name = $request->last_name;
        $carQuote->dob = $request->dob;
        $carQuote->nationality_id = $request->nationality_id;
        $carQuote->uae_license_held_for_id = $request->uae_license_held_for_id;
        $carQuote->year_of_manufacture = $request->year_of_manufacture;
        $carQuote->emirate_of_registration_id = $request->emirate_of_registration_id;
        $carQuote->car_type_insurance_id = $request->car_type_insurance_id;
        $carQuote->claim_history_id = $request->claim_history_id;
        $carQuote->premium = $request->premium;
        $carQuote->car_value = $request->car_value;
        $carQuote->seat_capacity = $request->seat_capacity;
        $carQuote->cylinder = $request->cylinder;
        $carQuote->vehicle_type_id = $request->vehicle_type_id;
        $carQuote->additional_notes = $request->additional_notes;
        $carQuote->car_make_id = $request->car_make_id;
        $carQuote->car_model_id = $request->car_model_id;
        $carQuote->currently_insured_with = $request->currently_insured_with;
        $carQuote->quote_updated_at = Carbon::now();
        $carQuote->is_quote_locked = true;
        $carQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/car/" . $carQuote->id)->with('success', 'Car Quote has been updated');
    }

    public function getEntity($id)
    {
        return $this->query->where('cqr.uuid', $id)->first();
    }

    public function updateChildRecord($id)
    {
        $childRecord = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();

        if (empty($childRecord)) {
            $childRecord = $this->createDetailEntity($id);
        }
    
        $childRecord->advisor_assigned_by_id = Auth::user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();
    }

    public function getSelectedLostReason($id)
    {
        $entity = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();
        if (!$entity) {
            $entity = $this->createDetailEntity($id);
        }
        return $entity;
    }

    public function createDetailEntity($id)
    {
        return CarQuoteRequestDetail::create([
            'car_quote_request_id' => $id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
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
            $query->where('a.auditable_type', 'App\Models\CarQuote')
            ->orWhere('a.auditable_type', 'App\Models\CarQuoteRequestDetail');
        })
        ->where(function ($query) {
           return  $query->whereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.quote_status_id')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.notes')"))
            ->orWhereNotNull(DB::raw("JSON_EXTRACT(a.new_values, '$.advisor_id')"));
        })
        ->where(function ($query) use ($id) {
            $detailObjId = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();
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

    public function getEntityPlain($id)
    {
        return CarQuote::where('id', $id)->first();
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "code" => "input|title",
            "renewal_batch" => "input|number|title",
            "advisor_id" => "select|title||multiple",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "previous_quote_policy_number" => "input|text|title",
            "previous_policy_expiry_date" => "input|date|title|range",
            "currently_insured_with" => "select|title|required",
            "currently_insured_with" => "select|title|required",
            "car_type_insurance_id" => "select|title|required",
            "quote_status_id" => "select|title",
            "car_make_id" => "select|title|required",
            "car_model_id" => "select|title|required",
            "vehicle_type_id" => "select|title|required",
            "next_followup_date" => "input|date|title|range",
            "previous_quote_policy_premium" => "input|title|number",
            "updated_at" => "input|date|title",
            "created_at" => "input|date|title|range",
            "lost_reason" => "input|text",
            "year_of_manufacture" => "select|title|required",
            "quote_status_id" => "select|title|multiple",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "dob" => "input|date|title|date|required",
            "nationality_id" => "select|title|required",
            "uae_license_held_for_id" => "select|title|required",
            "car_value" => "input|number|required",
            "seat_capacity" => "input|number|title|required",
            "cylinder" => "input|number|title|required",
            "emirate_of_registration_id" => "select|title|required",
            "claim_history_id" => "select|title|required",
            "source" => "input|text",
            "additional_notes" => "textarea|required",
            "is_ecommerce" => "|static|title|Yes,No",
            "payment_status_id" => "select|title",
            "transapp_code" => "readonly|none",
            "premium" => "input|number",
            "paid_at" => "input|date",
            "payment_gateway" => "input|title",
            "promo_code" => "input|title",
            "device" => "input|title",
            "previous_quote_id" => "input|text|title",
            "renewal_expiry_date" => "input|date|title|range",
            "policy_number" => "input|text|title",
            "order_reference" => "input",
            "payment_reference" => "input",
            "calculated_value" => "input|number",
            "created_by" => "input",
            "updated_by" => "input",
            "plan_id" => "select|title",
            "car_plan_provider_id" => "select|title",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'dob':
                $title = "Date of Birth";
                break;
            case 'currently_insured_with':
                $title = "Currently Insured With";
                break;
            case 'uae_license_held_for_id':
                $title = "UAE licence held for";
                break;
            case 'car_make_id':
                $title = "Car Make";
                break;
            case 'car_model_id':
                $title = "Car Model";
                break;
            case 'nationality_id':
                $title = "Nationality";
                break;
            case 'mobile_no':
                $title = "Phone Number";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'emirate_of_registration_id':
                $title = "Emirate Of Registration";
                break;
            case 'car_type_insurance_id':
                $title = "Type of Car Insurance";
                break;
            case 'claim_history_id':
                $title = "Claim History";
                break;
            case 'code':
                $title = "CDB ID";
                break;
            case 'advisor_id':
                $title = "Assigned To";
                break;
            case 'payment_status_id':
                $title = "Payment Status";
                break;
            case 'plan_id':
                $title = "Plan Name";
                break;
            case 'car_plan_provider_id':
                $title = "Provider Name";
                break;
            case 'is_ecommerce':
                $title = "Ecommerce";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            case 'payment_gateway':
                $title = "Payment Method";
                break;
            case 'promo_code':
                $title = "Advisor/Promo Code";
                break;
            case 'quote_status_id':
                $title = "Quote Status";
                break;
            case 'device':
                $title = "Device";
                break;
            case 'year_of_manufacture':
                $title = "Year of Manufacture";
                break;
            case 'vehicle_type_id':
                $title = "Vehicle Type";
                break;
            case 'previous_quote_id':
                $title = "Previous Quote ID";
                break;
            case 'renewal_batch':
                $title = "Renewal Batch #";
                break;
            case 'renewal_expiry_date':
                $title = "Policy Expiry Date";
                break;
            case 'policy_number':
                $title = "Policy Number";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            case 'seat_capacity':
                $title = "Seat Capacity";
                break;
            case 'cylinder':
                $title = "Cylinder";
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
            default:
                break;
        }
        return $title;
    }

    public function getCarOverDueFollowups()
    {
        $query = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.id',
                'cqr.uuid',
                'cqr.code',
                DB::raw("CONCAT_WS(' ',cqr.first_name,cqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'cqr.created_at as createdAt',
                'cqr.quote_status_id',
                'cqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'cqr.updated_at',
                'cqr.source as leadSource',
                'cqr.premium',
                'cqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqrd.advisor_assigned_by_id')
            ->where('cqrd.next_followup_date', '<', date('Y-m-d H:i:s'))
            ->whereIn('qs.text', ['Followed Up','Qualification Pending', 'Quoted', 'FTC Pending', 'FTC Sent', 'Missing Documents Requested', 'Policy Documents Pending', 'Payment Pending', 'Pending with UW', 'Application Pending', 'In Negotiation'])
            ->where('cqr.advisor_id', Auth::user()->id);
        return $query;
    }

    public function getCarLeadsForAdvisor($request)
    {
        $query = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.id',
                'cqr.uuid',
                'cqr.code',
                'qs.text as leadStatus',
                'cqr.created_at as createdAt',
                'vt.text as vehicleType',
                'cqr.quote_status_id',
                'cqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'cqr.updated_at',
                'cqr.source as leadSource',
                'cqrd.next_followup_date as nextFollowupDate',
                'cqr.previous_quote_policy_premium as previousPolicyPremium',
                'ps.text as paymentStatus',
                'cqr.renewal_batch as renewalBatch',
                'cqr.previous_quote_policy_number as previousPolicyNumber',
                DB::raw('DATE_FORMAT(cqr.previous_policy_expiry_date, "%d-%m-%Y") as previousPolicyExpiryDate'),
                'cmake.text as carMake',
                'cmodel.text as carModel',
                'cqr.year_of_manufacture as yearOfManufacture',
                'cti.text as typeOfCarInsurance',
                'cqr.currently_insured_with as currentlyInsuredWith',
                'ua.name as assignedTo',
                'cqr.updated_at as updatedAt',
                'ls.text as lostReason',
                'cqr.first_name as firstName',
                'cqr.last_name as lastName',
            )
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqrd.advisor_assigned_by_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('vehicle_type as vt', 'vt.id', '=', 'cqr.vehicle_type_id')
            ->leftJoin('car_make as cmake', 'cmake.id', '=', 'cqr.car_make_id')
            ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'cqr.car_model_id')
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
            ->leftJoin('users as ua', 'ua.id', '=', 'cqr.advisor_id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'cqrd.lost_reason_id')
            ->where('qs.text', '!=', quoteStatusCode::FAKE)
            ->where('cqr.advisor_id', Auth::user()->id)
            ->orderBy('cqr.created_at', "DESC");

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $columnName = $request->get('columns')[$column]['name'];
            $query->orderBy($this->getSortingColumnNameWithPrefix($columnName), $direction);
        }

        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = $this->parseDate($request->startedAt, true);
            $dateTo = $this->parseDate($request->endAt, false);
            $query->whereBetween('cqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = $this->parseDate($request->nfdSart, true);
            $dateTo = $this->parseDate($request->nfdEnd, false);
            $query->whereBetween('cqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('cqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('cqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('cqr.quote_status_id', $request->leadStatus);
        }
        if (isset($request->paymentStatus)) {
            $query->where('cqr.payment_status_id', $request->paymentStatus);
        }
        if (isset($request->renewal_batch)) {
            $query->where('cqr.renewal_batch', $request->renewal_batch);
        }
        if (isset($request->previous_policy_number)) {
            $query->where('cqr.previous_quote_policy_number', $request->previous_policy_number);
        }
        if (isset($request->previous_policy_expiry_date) && $request->previous_policy_expiry_date != '' && $request->previous_policy_expiry_date_end != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['previous_policy_expiry_date_end'])->endOfDay()->toDateTimeString();
            $query->whereBetween('cqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
        }
        if (Auth::user()->isRenewalAdvisor()) {
            $query->whereNotNull('cqr.previous_quote_id');
            $query->orderBy('cqr.previous_policy_expiry_date', 'ASC');
        }
        if (isset($request->isEcommerce)) {
            $isEcommerce = $request->isEcommerce == "Yes" ? 1 : 0;
            $query->where('cqr.is_ecommerce', $isEcommerce);
        }
        if (isset($request->createdAtStart) && isset($request->createdAtEnd) && $request->createdAtStart != '' && $request->createdAtEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request['createdAtStart'])->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request['createdAtEnd'])->endOfDay()->toDateTimeString();
            $query->whereBetween('cqr.created_at', [$dateFrom, $dateTo]);
        }
        if (isset($request->vehicleType)) {
            $query->where('cqr.vehicle_type_id', $request->vehicleType);
        }
        if (isset($request->typeOfCarInsurance)) {
            $query->where('cqr.car_type_insurance_id', $request->typeOfCarInsurance);
        }
        if (isset($request->currentlyInsuredWith)) {
            $query->where('cqr.currently_insured_with', $request->currentlyInsuredWith);
        }
        return $query;
    }
    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            if ($isStartOfDay) {
                return Carbon::createFromFormat('Y-m-d', $date)->startOfDay()->toDateTimeString();
            } else {
                return Carbon::createFromFormat('Y-m-d', $date)->endOfDay()->toDateTimeString();
            }
        }
    }

    public function walkTree ($userId) {
        $childs = User::where('manager_id', $userId)->pluck('id');
        foreach ($childs as $child) {
            $nextChilds = User::where('manager_id', $child)->pluck('id');
            if(count($nextChilds) > 0) {
                $this->walkTree($child);
            }
            array_push($this->childUserIds, $child);
        }
    }

    public function getGridData($model, $request)
    {
        $searchProperties = [];
        $isRenewalUser = Auth::user()->isRenewalUser();
        if ($isRenewalUser) {
            $searchProperties = $model->renewalSearchProperties;
        } else {
            $searchProperties = $model->searchProperties;
        }

        if ($request->ajax()) {
            if(Auth::user()->isManagerOrDeputy()){
                if (!Auth::user()->hasRole('CAR_RENEWAL_MANAGER')) {
                    $this->walkTree(Auth::user()->id); // get all childs of the user
                    array_push($this->childUserIds, Auth::user()->id); // add the user id to the array to fetch directly assigned leads as well
                    $this->query->whereIn('cqr.advisor_id', $this->childUserIds);	// fetch leads assigned to the user or his childs
                }
            }

            if(Auth::user()->isSpecificTeamAdvisor('Car')){
                // if user has advisor Role then fetch leads assigned to the user only
                $this->query->where('cqr.advisor_id', Auth::user()->id);	// fetch leads assigned to the user
            }
            
            if (!isset($request->email) && $request->email == '') {
                $this->query->where('qs.text', '!=', 'Fake');
            }
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = $this->parseDate($request['assigned_to_date_start'], true);
                $dateTo = $this->parseDate($request['assigned_to_date_end'], false);
                $this->query->whereBetween('cqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->renewal_expiry_date) && $request->renewal_expiry_date != '') {
                $dateFrom = $this->parseDate($request['renewal_expiry_date'], true);
                $dateTo = $this->parseDate($request['renewal_expiry_date_end'], false);
                $this->query->whereBetween('cqr.previous_policy_expiry_date', [$dateFrom, $dateTo]);
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = $this->parseDate($request['next_followup_date'], true);
                $dateTo = $this->parseDate($request['next_followup_date_end'], false);
                $this->query->whereBetween('cqrd.next_followup_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = $this->parseDate($request['created_at'], true);
                $dateTo = $this->parseDate($request['created_at_end'], false);
                $this->query->whereBetween('cqr.created_at', [$dateFrom, $dateTo]);
            }
            if (Auth::user()->hasRole('ADMIN')) {
                array_push($searchProperties, 'is_ecommerce');
                array_push($searchProperties, 'payment_status_id');
            }
            if (Auth::user()->isRenewalAdvisor()) {
                $this->query->where('cqr.advisor_id', Auth::user()->id);
            }
            if (Auth::user()->isRenewalUser()) {
                $this->query->orderBy('cqr.previous_policy_expiry_date', 'ASC');
            }

            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at" && $item != "renewal_expiry_date") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else if ($item == 'advisor_id' && is_array($request[$item]) && !empty($request[$item])) {
                        if($request[$item][0] == 'null')
                            $this->query->whereNull('advisor_id');
                        else
                            $this->query->whereIn('advisor_id', $request[$item]);
                    }
                    else if ($item == 'quote_status_id' && is_array($request[$item]) && !empty($request[$item])) {
                        $this->query->whereIn('quote_status_id', $request[$item]);
                    } else {
                        $searchedValue = str_contains($request[$item], 'Yes') || str_contains($request[$item], 'No') ? ($request[$item] == 'Yes' ? 1 : 0) : $request[$item];
                        if ($item == 'policy_number') {
                            $this->query->where('previous_quote_policy_number', $searchedValue);
                        }
                        else {
                            $this->query->where($this->getQuerySuffix($item) . '.' . $item, $searchedValue);
                        }
                    }
                }
            }
        }

        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $columnName = $request->get('columns')[$column]['name'];
            return $this->query->orderBy($this->getSortingColumnNameWithPrefix($columnName), $direction);
        } else {
            if (Auth::user()->isRenewalUser()) {
                return $this->query->whereNotNull('cqr.previous_quote_id');
            }
            return $this->query->orderBy('cqr.created_at', 'DESC');
        }
    }

    private function getSortingColumnNameWithPrefix($columnName)
    {
        switch ($columnName) {
            case 'created_at':
                return 'cqr.created_at';
                break;
            case 'updated_at':
                return 'cqr.updated_at';
                break;
            case 'next_followup_date':
                return 'cqrd.next_followup_date';
                break;
            default:
                break;
        }
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'created_at':
                return 'cqr.created_at';
                break;
            case 'updated_at':
                return 'cqr.updated_at';
                break;
            case 'next_followup_date':
                return 'cqrd.next_followup_date';
                break;
            case 'uae_license_held_for':
                return 'ulhf';
                break;
            case 'car_make':
                return 'cmake';
                break;
            case 'payment_status':
                return 'ps';
                break;
            case 'car_model':
                return 'cmodel';
                break;
            case 'nationality':
                return 'n';
                break;
            case 'emirates':
                return 'e';
                break;
            case 'insurance_provider':
                return 'ip';
                break;
            case 'claim_history':
                return 'ch';
                break;
            case 'car_type_insurance':
                return 'cti';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'plan':
                return 'cp';
                break;
            case 'car_plan_provider':
                return 'cpip';
                break;
            case 'quote_status':
                return 'qs';
                break;
            default:
                return 'cqr';
                break;
        }
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query =  DB::table('car_quote_request as cqr')
            ->select(
                'cqr.id',
                'cqr.uuid',
                'cqr.first_name',
                'cqr.code',
                'cqr.last_name',
                'cqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Car' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
            ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->orderBy('advisor_id', 'ASC');

        if (!empty($CDBID)) {
            $query->where('cqr.CDBID', $CDBID);
        }
        if (!empty($email)) {
            $query->where('cqr.email', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('cqr.mobile_no', $mobile_no);
        }
        return $query;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id,paid_at,lost_reason,payment_status_id,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,renewal_expiry_date,renewal_batch,premium,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium",
            "list" => "additional_notes,email,mobile_no,paid_at,renewal_batch,renewal_expiry_date,plan_id,car_plan_provider_id,payment_gateway,currently_insured_with,promo_code,car_make_id,car_model_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,nationality_id,dob,year_of_manufacture,uae_license_held_for_id,car_value,emirate_of_registration_id,claim_history_id,car_type_insurance_id,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium",
            "update" => "id,advisor_id,paid_at,renewal_expiry_date,payment_status_id,lost_reason,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,renewal_batch,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,quote_status_id",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'next_followup_date'];
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'vehicle_type_id', 'previous_policy_expiry_date', 'is_ecommerce', 'payment_status_id', 'renewal_batch', 'car_type_insurance_id', 'currently_insured_with','previous_quote_policy_number'];
        $model->renewalSkipProperties = [
            "create" => "id,advisor_id,paid_at,renewal_expiry_date,renewal_batch,lost_reason,payment_status_id,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,premium,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium",
            "list" => "additional_notes,email,mobile_no,paid_at,plan_id,car_plan_provider_id,payment_gateway,promo_code,source,seat_capacity,cylinder,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,nationality_id,dob,uae_license_held_for_id,car_value,emirate_of_registration_id,claim_history_id,transapp_code,is_ecommerce,payment_status_id,policy_number,renewal_expiry_date,premium,year_of_manufacture",
            "update" => "id,advisor_id,paid_at,payment_status_id,lost_reason,plan_id,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,renewal_expiry_date,source,transapp_code,quote_status_id,renewal_batch,policy_number,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium",
            "show" => "",
        ];
    }

    public function getQuotePlans($id)
    {
        $quoteUuId = CarQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = Config::get('constants.KEN_API_ENDPOINT') . '/get-car-quote-plans';
        $plansApiToken = Config::get('constants.KEN_API_TOKEN');
        $plansApiTimeout = Config::get('constants.KEN_API_TIMEOUT');
        $plansApiUserName = Config::get('constants.KEN_API_USER');
        $plansApiPassword = Config::get('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName . ":" . $plansApiPassword);

        $plansDataArr = array(
            "quoteUID" => $quoteUuId,
            "lang" => "en",
        );

        $client = new \GuzzleHttp\Client();

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json', 'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic ' . $authBasic
                    ],
                    'body' => json_encode($plansDataArr),
                    'timeout' => $plansApiTimeout,
                ]
            );

            $getStatusCode = $kenRequest->getStatusCode();

            if ($getStatusCode == 200) {
                $getContents = $kenRequest->getBody();
                $getdecodeContents = json_decode($getContents);
                return $getdecodeContents;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } else if (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } else {
                $responseBodyAsString = $response->msg;
            }

            return $responseBodyAsString;
        }
    }

    public function getCarQuotePlanAddons($id)
    {
        $listCarQuotePlanAddons = DB::table('car_addon_option')
            ->select(
                'car_addon.text AS car_addon_text',
                'car_addon_option.value AS car_addon_option_value',
                'car_addon_option.price AS car_addon_option_price',
                'car_addon.type AS car_addon_type'
            )
            ->leftJoin('car_addon', 'car_addon.id', '=', 'car_addon_option.addon_id')
            ->leftJoin('car_quote_request_addon', 'car_addon_option.id', '=', 'car_quote_request_addon.addon_option_id')
            ->leftJoin('car_quote_request', 'car_quote_request.id', '=', 'car_quote_request_addon.quote_request_id')
            ->where('car_quote_request.uuid', $id)->get();
        return $listCarQuotePlanAddons;
    }

    public function carPlanModify($request)
    {
        $apiEndPoint = Config::get('constants.KEN_API_ENDPOINT') . '/save-manual-car-quote-plan';
        $apiToken = Config::get('constants.KEN_API_TOKEN');
        $apiTimeout = Config::get('constants.KEN_API_TIMEOUT');
        $apiUserName = Config::get('constants.KEN_API_USER');
        $apiPassword = Config::get('constants.KEN_API_PWD');

        if(isset($request->is_create)) {
            if($request->is_create == 1) {
                $discountedPremium = $request->actual_premium;
                $isUpdate = false;
            } else {
                $discountedPremium = $request->discounted_premium;
                $isUpdate = true;
            }
        } else {
            $discountedPremium = $request->actual_premium;
        }

        $addons = [];
        if($request->addons != null && count($request->addons) > 0) {
            $addons = $request->addons;
        } else {
            $addons = [];
        }

        $carPlanData = array(
            "quoteUID" => $request->car_quote_uuid,
            "update" => $isUpdate,
            "plans" => array(
                [
                    "planId" => (int)$request->car_plan_id,
                    "actualPremium" => (float)$request->actual_premium,
                    "carValue" => (float)$request->car_value,
                    "excess" => (float)$request->excess,
                    "discountPremium" => (float)$discountedPremium,
                    "isDisabled" => filter_var($request->is_disabled, FILTER_VALIDATE_BOOLEAN),
                    "addons" => $addons,
                ]

            )
        );

        $apiCreds = array(
            "apiEndPoint" => $apiEndPoint,
            "apiToken" => $apiToken,
            "apiTimeout" => $apiTimeout,
            "apiUserName" => $apiUserName,
            "apiPassword" => $apiPassword,
        );

        $response = $this->httpService->processRequest($carPlanData, $apiCreds);

        return $response;
    }

    public function getDuplicateEntityByCode($code)
    {
        return CarQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function createDuplicate($parentRecord)
    {
        $quote = new CarQuote();
        $quote->parent_duplicate_quote_id = $parentRecord->code;
        $response = CapiRequestService::getUUID(QuoteTypeId::Car);
        if($response) {
            $quote->uuid = $response->uuid;
            $quote->code = 'CAR-'. $response->uuid;
        }
        $quote->quote_status_id = QuoteStatus::where('text', 'New Lead')->first()->id;
        $quote->first_name = $parentRecord->first_name;
        $quote->last_name = $parentRecord->last_name;
        $quote->email = $parentRecord->email;
        $quote->mobile_no = $parentRecord->mobile_no;
        $quote->advisor_id = Auth::user()->id;
        $quote->save();
    }

    public function getQuoteByUuid($uuid)
    {
        return CarQuote::where('uuid', '=', $uuid)->first();
    }

    public function getPlans($id)
    {
        $quotePlans = $this->getQuotePlans($id);

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string' && isset($quotePlans->quotes->plans)) {
                $listQuotePlans = $quotePlans->quotes->plans;
            } else if(!isset($quotePlans->quotes->plans)) {
                $listQuotePlans = 'Plans not available!';
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $listQuotePlans;
    }

    public function carAssumptionsUpdateProcess($request)
    {
        $updateQuote = CarQuote::find($request->car_quote_id);
        $updateQuote->cylinder = $request->cylinder;
        $updateQuote->seat_capacity = $request->seat_capacity;
        $updateQuote->vehicle_type_id = $request->vehicle_type_id;
        $updateQuote->is_modified = $request->is_modified;
        $updateQuote->is_bank_financed = $request->is_bank_financed;
        $updateQuote->is_gcc_standard = $request->is_gcc_standard;
        $updateQuote->current_insurance_status = $request->current_insurance_status;
        $updateQuote->year_of_first_registration = $request->year_of_first_registration;
        $updateQuote->quote_updated_at = Carbon::now();
        $updateQuote->is_quote_locked = true;
        $updateQuote->save();

        return $updateQuote->id;
    }
}
