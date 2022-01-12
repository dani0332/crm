<?php

namespace App\Services;

use App\Models\CarMake;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\YearOfManufacture;
use Illuminate\Http\Request;
use Config;
use DB;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CarQuoteService extends BaseService
{
    protected $query;
    public function __construct()
    {
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
                'ym.text AS year_of_manufacture_text',
                'cqrd.next_followup_date',
                'cqrd.notes',
                'vt.text as vehicle_type_id_text',
                'cqr.currently_insured_with',
                'ciw.text as currently_insured_with_text',
            )
            ->leftJoin('nationality as n', 'n.id', '=', 'cqr.nationality_id')
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('car_make as cmake', 'cmake.id', '=', 'cqr.car_make_id')
            ->leftJoin('uae_license_held_for as ulhf', 'ulhf.id', '=', 'cqr.uae_license_held_for_id')
            ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'cqr.car_model_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'cqr.emirate_of_registration_id')
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
            ->leftJoin('claim_history as ch', 'ch.id', '=', 'cqr.claim_history_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('insurance_provider as ciw', 'ciw.text', '=', 'cqr.currently_insured_with')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('vehicle_type as vt', 'vt.id', '=', 'cqr.vehicle_type_id')
            ->leftJoin('year_of_manufacture as ym', 'ym.text', '=', 'cqr.year_of_manufacture');
    }

    public function saveCarQuote(Request $request)
    {
        $yearOfManufactureText = YearOfManufacture::where('id', '=', $request->year_of_manufacture)->value('text');
        $insuranceProviderText = InsuranceProvider::where('id', '=', $request->currently_insured_with)->value('text');
        $carMakeId = CarMake::where('code', '=', $request->car_make_id)->value('id');
        $appName = Config::get('constants.APP_NAME');
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
            "carMakeId" => $carMakeId, // ID
            "carModelId" => $request->car_model_id, // ID
            "currentlyInsuredWith" => $insuranceProviderText, // TEXT
            "source" => $appName,
            "referenceUrl" => $appUrl,
        );
        if (Auth::user()->hasRole("CAR_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-car-quote', $dataArr);
    }

    public function updateCarQuote(Request $request, $id)
    {
        $carQuote = CarQuote::find($id);
        $carQuote->first_name = $request->first_name;
        $carQuote->last_name = $request->last_name;
        $carQuote->email = $request->email;
        $carQuote->mobile_no = $request->mobile_no;
        $carQuote->dob = $request->dob;
        $carQuote->nationality_id = $request->nationality_id;
        $carQuote->uae_license_held_for_id = $request->uae_license_held_for_id;
        $carQuote->year_of_manufacture = $request->year_of_manufacture;
        $carQuote->emirate_of_registration_id = $request->emirate_of_registration_id;
        $carQuote->car_type_insurance_id = $request->car_type_insurance_id;
        $carQuote->claim_history_id = $request->claim_history_id;
        $carQuote->additional_notes = $request->additional_notes;
        $carQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/car/" . $carQuote->id)->with('success', 'Car Quote has been updated');
    }

    public function getEntity($id)
    {
        return $this->query->where('cqr.uuid', $id)->first();
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
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "quote_status_id" => "select|title",
            "advisor_id" => "select|title|required",
            "dob" => "input|title|date|required",
            "is_ecommerce" => "|static|title|Yes,No",
            "payment_status_id" => "select|title",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "car_value" => "input|number|required",
            "seat_capacity" => "input|number|required",
            "cylinder" => "input|number|required",
            "vehicle_type_id" => "select|title|required",
            "nationality_id" => "select|title|required",
            "uae_license_held_for_id" => "select|title|required",
            "car_make_id" => "select|title|required",
            "car_model_id" => "select|title|required",
            "year_of_manufacture" => "select|title|required",
            "emirate_of_registration_id" => "select|title|required",
            "premium" => "input|number",
            "paid_at" => "input|date",
            "payment_gateway" => "input|title",
            "currently_insured_with" => "select|title|required",
            "promo_code" => "input|title",
            "device" => "input|title",
            "policy_number" => "input",
            "previous_quote_id" => "input",
            "order_reference" => "input",
            "payment_reference" => "input",
            "calculated_value" => "input|number",
            "created_by" => "input",
            "updated_by" => "input",
            "car_type_insurance_id" => "select|title|required",
            "claim_history_id" => "select|title|required",
            "additional_notes" => "textarea|required",
            "plan_id" => "select|title",
            "car_plan_provider_id" => "select|title",
            "quote_status_id" => "select|title",
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
            default:
                break;
        }
        return $title;
    }

    public function getCarLeadsForAdvisor($request)
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
            )
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqrd.advisor_assigned_by_id')
            ->where('cqr.advisor_id', Auth::user()->id);
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('cqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('cqr.code', $request->cdbId);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('cqr.quote_status_id', $request->leadStatus);
        }
        return $query;
    }

    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('cqr.created_at', [$dateFrom, $dateTo]);
            }
            if (Auth::user()->hasRole('ADMIN')) {
                array_push($searchProperties, 'is_ecommerce');
                array_push($searchProperties, 'payment_status_id');
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else {
                        $searchedValue = str_contains($request[$item], 'Yes') || str_contains($request[$item], 'No') ? ($request[$item] == 'Yes' ? 1 : 0) : $request[$item];
                        $this->query->where($this->getQuerySuffix($item) . '.' . $item, $searchedValue);
                    }
                }
            }
        }
        return $this->query->orderBy('cqr.advisor_id', 'ASC');
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
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
            "create" => "id,advisor_id,premium,paid_at,payment_status_id,plan_id,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by",
            "list" => "additional_notes,email,mobile_no,premium,paid_at,plan_id,car_plan_provider_id,payment_gateway,currently_insured_with,promo_code,car_make_id,car_model_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,nationality_id,dob,year_of_manufacture,uae_license_held_for_id,car_value,emirate_of_registration_id,claim_history_id,car_type_insurance_id",
            "update" => "id,advisor_id,premium,paid_at,payment_status_id,plan_id,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,updated_at,currently_insured_with,promo_code,car_make_id,car_model_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by",
            "show" => "",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at'];
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
}
