<?php

namespace App\Services;

use App\Builders\CarQuoteQueryBuilder;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Facades\Ken;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\Lookup;
use App\Models\QuoteBatches;
use App\Models\QuoteRequestEntityMapping;
use App\Models\Team;
use App\Models\Tier;
use App\Models\UserTeams;
use App\Models\VehicleChassisDetail;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\OCRTrait;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDF;

class CarQuoteService extends BaseService
{
    private $query;
    protected $httpService;
    protected $childUserIds = [];
    protected $leadAllocationService;
    protected $sendEmailCustomerService;
    protected $applicationStorageService;
    protected $activityService;
    protected $capiService;

    private const REQUIRED = 'required';
    private const STRING = 'string';
    private const REQUIRED_STRING = self::REQUIRED.'|'.self::STRING;

    // Traits
    use GenericQueriesAllLobs;
    use OCRTrait;
    use TeamHierarchyTrait;

    public function __construct(
        HttpRequestService $httpService,
        LeadAllocationService $leadAllocationService,
        SendEmailCustomerService $sendEmailCustomerService,
        ApplicationStorageService $applicationStorageService,
        ActivitiesService $activityService,
        protected CarQuoteQueryBuilder $carQuoteQueryBuilder,
        CapiService $capiService
    ) {
        $this->leadAllocationService = $leadAllocationService;
        $this->httpService = $httpService;
        $this->applicationStorageService = $applicationStorageService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->activityService = $activityService;
        $this->capiService = $capiService;
    }

    public function saveCarQuote(Request $request)
    {
        $registrationType = $request->registration_type ?? CarRegistrationType::PERSONAL;
        $vehicleUse = $request->vehicle_use ?? CarVehicleUse::PRIVATE;
        $firstName = $request->first_name ?? null;
        $lastName = $request->last_name ?? null;
        $driverName = $request->driver_name ?? null;

        if ($registrationType == CarRegistrationType::COMPANY) {
            $name = explode(' ', $request->company_contact_name);
            $firstName = reset($name);
            unset($name[0]);
            $lastName = implode(' ', $name) ?? null;
        }

        $dataArr = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $request->email,
            'address' => $request->address,
            'mobileNo' => $request->mobile_no,
            'dob' => $request->dob ?? null,
            'nationalityId' => $request->nationality_id ?? null,
            'uaeLicenseHeldForId' => $request->uae_license_held_for_id ?? null,
            'backHomeLicenseHeldForId' => $request->back_home_license_held_for_id ?? null,
            'yearOfManufacture' => $request->year_of_manufacture,
            'emirateOfRegistrationId' => $request->emirate_of_registration_id,
            'carTypeInsuranceId' => $request->car_type_insurance_id,
            'claimHistoryId' => $request->claim_history_id,
            'hasNcdSupportingDocuments' => $request->has_ncd_supporting_documents,
            'additionalNotes' => $request->additional_notes,
            'carValue' => $request->car_value_tier,
            'carValueTier' => $request->car_value_tier,
            'seatCapacity' => $request->seat_capacity,
            'cylinder' => $request->cylinder,
            'vehicleTypeId' => $request->vehicle_type_id,
            'trim' => $request->trim,
            'premium' => $request->premium,
            'carMakeId' => $request->car_make_id,
            'carModelId' => $request->car_model_id, // ID
            'currentlyInsuredWith' => $request->currently_insured_with,
            'source' => $request->lead_type === 'expert_advisor_model' ? LeadSourceEnum::EA_IMCRM : config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
            'gender' => $request->gender ?? null,
            'chassisNumber' => $request->chassis_number,
            'registrationType' => $registrationType,
            'vehicleUse' => $vehicleUse,
            'companyName' => $request->company_name ?? null,
            'companyAddress' => $request->company_address ?? null,
            'pointOfContactName' => $request->company_contact_name ?? null,
            'businessActivityId' => $request->business_activity_id ?? null,
            'driverName' => $driverName,
            // Lead source fields from CreateLeadModal
            'subSourceId' => $request->sub_source_id ?? null,
            'subSourceOptionsId' => $request->sub_source_options_id ?? null,
            'driverEidNumber' => $request->driver_emirates_id_number ?? null,
            'driverGender' => $request->driver_gender ?? null,
        ];

        LoggerService::info('saveQuote '.print_r([
            'subSourceId' => $request->sub_source_id ?? null,
            'subSourceOptionsId' => $request->sub_source_options_id ?? null,
            'additional_notes' => $request->additional_notes ?? null,
        ], true));

        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }
        LoggerService::info('Create triggered from IMCRM for Car Quote request with email : '.$request->email.' and sending request to CAPI');

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-car-quote', $dataArr, CarQuote::class);

        if (isset($response->quoteUID)) {
            $this->selfAssign(QuoteTypes::CAR, $response->quoteUID);

            // Add chassis number details
            $carMake = CarMake::find($request->car_make_id);
            $carModel = CarModel::find($request->car_model_id);
            $carMakeAndModel = trim(($carMake ? $carMake->text : '').' '.($carModel ? $carModel->text : ''));

            $data = [
                'chassis_number' => $request->chassis_number,
                'vehicle_make_model' => $carMakeAndModel,
                'cylinder' => $request->cylinder,
                'seating_capacity' => $request->seat_capacity,
                'vehicle_trim' => $request->trim,
            ];

            $this->saveVehicleChassisDetails($response->quoteUID, $data);
        }

        return $response;
    }

    public function saveVehicleChassisDetails($uuid, $data): void
    {
        LoggerService::info('Saving vehicle chassis details', ['uuid' => $uuid, 'data' => $data]);

        VehicleChassisDetail::updateOrCreate(
            ['chassis_number' => $data['chassis_number']],
            array_merge($data, ['uuid' => $uuid, 'quote_type_id' => QuoteTypes::CAR->id()]),
        );
    }

    public function updateCarQuote(Request $request, $id)
    {
        $carQuote = CarQuote::where('uuid', $id)->first();

        $oldCarValue = $carQuote->car_value;
        $oldDob = $carQuote->dob;
        $oldBodyType = $carQuote->vehicle_type_id;
        LoggerService::info('Update triggered from IMCRM for Car Quote request with uuid : '.$carQuote->code);

        $registrationType = $request->registration_type;
        $vehicleUse = $request->vehicle_use ?? CarVehicleUse::PRIVATE;
        $carQuote->registration_type = $registrationType;
        $carQuote->vehicle_use = $vehicleUse;
        $carQuote->business_activity_id = $request->business_activity_id ?? null;
        $carQuote->company_name = $request->company_name ?? null;
        $carQuote->company_address = $request->company_address ?? null;

        $driverName = $request->driver_name ?? null;
        $firstName = $request->first_name ?? null;
        $lastName = $request->last_name ?? null;

        $entityMapping = QuoteRequestEntityMapping::with('entity')
            ->where('quote_type_id', QuoteTypeId::Car)
            ->where('quote_request_id', $carQuote->id)
            ->first();
        if ($registrationType == CarRegistrationType::COMPANY) {

            $name = explode(' ', $request->company_contact_name);
            $firstName = reset($name);
            unset($name[0]);
            $lastName = implode(' ', $name) ?? null;

            if (! $entityMapping) {

                $entity = Entity::create([
                    'company_name' => $carQuote->company_name,
                ]);
                $entity->refresh();
                $entityId = $entity->id;
                $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entityId]);

                QuoteRequestEntityMapping::updateOrCreate([
                    'quote_type_id' => QuoteTypeId::Car,
                    'quote_request_id' => $carQuote->id,
                ], ['entity_id' => $entityId]);
            }

        } else {
            if ($entityMapping) {

                $entityMappingCount = $entityMapping->entity->quoteRequestEntityMapping->count();
                $entityRecord = $entityMapping->entity;
                $entityMapping->delete();
                if ($entityMappingCount == 1) {
                    $entityRecord->delete();
                }
            }
        }

        if (Auth::user()->can(PermissionsEnum::COMPANY_PRIVATE_CAR_DRIVER_UPDATES)) {
            if (
                $registrationType == CarRegistrationType::COMPANY &&
                $vehicleUse == CarVehicleUse::PRIVATE
            ) {

                $carQuote->vehicleDriverDetail()->updateOrCreate(
                    ['quoteable_type' => CarQuote::class, 'quoteable_id' => $carQuote->id],
                    [
                        'driver_eid_number' => $request->driver_emirates_id_number ?? null,
                        'driver_gender' => $request->driver_gender ?? null,
                    ]
                );

            } else {
                $vehicleDriverDetail = $carQuote->vehicleDriverDetail;
                if ($vehicleDriverDetail) {
                    $vehicleDriverDetail->update([
                        'driver_eid_number' => null,
                        'driver_gender' => null,
                    ]);
                }
            }
        }

        $carQuote->first_name = $firstName;
        $carQuote->last_name = $lastName;
        $carQuote->dob = $request->dob ?? null;
        $carQuote->nationality_id = $request->nationality_id ?? null;
        $carQuote->uae_license_held_for_id = $request->uae_license_held_for_id ?? null;
        $carQuote->back_home_license_held_for_id = $request->back_home_license_held_for_id ?? null;
        $carQuote->driver_name = $driverName;

        if ($request->year_of_manufacture) {
            $carQuote->year_of_manufacture = $request->year_of_manufacture;
        }
        if ($request->emirate_of_registration_id) {
            $carQuote->emirate_of_registration_id = $request->emirate_of_registration_id;
        }
        if ($request->car_type_insurance_id) {
            $carQuote->car_type_insurance_id = $request->car_type_insurance_id;
        }
        if ($request->claim_history_id) {
            $carQuote->claim_history_id = $request->claim_history_id;
        }
        if (isset($request->has_ncd_supporting_documents)) {
            $carQuote->has_ncd_supporting_documents = $request->has_ncd_supporting_documents;
        }
        if ($request->premium) {
            $carQuote->premium = $request->premium;
        }
        if ($request->car_value) {
            $carQuote->car_value = $request->car_value;
        }
        if ($request->seat_capacity) {
            $carQuote->seat_capacity = $request->seat_capacity;
        }
        if ($request->cylinder) {
            $carQuote->cylinder = $request->cylinder;
        }
        if ($request->vehicle_type_id) {
            $carQuote->vehicle_type_id = $request->vehicle_type_id;
        }
        if ($request->additional_notes) {
            $carQuote->additional_notes = $request->additional_notes;
        }
        if ($request->car_make_id) {
            $carQuote->car_make_id = $request->car_make_id;
        }
        if ($request->car_model_id) {
            $carQuote->car_model_id = $request->car_model_id;
        }
        if ($request->currently_insured_with) {
            $carQuote->currently_insured_with = $request->currently_insured_with;
        }
        if ($request->gender) {
            $carQuote->gender = $request->gender;
        }

        // Update lead source fields from CreateLeadModal
        if ($request->has('sub_source_id')) {
            $carQuote->sub_source_id = $request->sub_source_id;
        }
        if ($request->has('sub_source_options_id')) {
            $carQuote->sub_source_options_id = $request->sub_source_options_id;
        }
        // Primary ref id no longer supported from CreateLeadModal

        $carQuote->quote_updated_at = Carbon::now();
        $carQuote->is_quote_locked = true;
        if ($request->trim) {
            $carQuote->car_model_detail_id = $request->trim;
        }
        if ($request->renewal_batch) {
            $carQuote->renewal_batch = isset($request->renewal_batch) ? $request->renewal_batch : null;
        }
        if ($request->previous_quote_policy_number) {
            $carQuote->previous_quote_policy_number = isset($request->previous_quote_policy_number) ? $request->previous_quote_policy_number : null;
        }
        if ($request->previous_policy_expiry_date) {
            $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');
            $carQuote->previous_policy_expiry_date = isset($request->previous_policy_expiry_date) ? Carbon::parse($request->previous_policy_expiry_date)->format($dateFormat) : null;
        }

        if ($request->car_value_tier) {
            $carQuote->car_value_tier = $request->car_value_tier;

            $originalValue = $carQuote->car_value; // taking backup of original car_value

            $carQuote->car_value = $request->car_value_tier; // adding value tier because tier function uses car_value

            $selectedTier = $this->leadAllocationService->getTierForValue($carQuote);

            $carQuote->car_value = $originalValue; // adding back the original value since tier is now selected.

            $carQuote->tier_id = $selectedTier->id;
            $carQuote->cost_per_lead = $selectedTier->cost_per_lead;
        }

        $carQuote->updated_by = auth()->user()->email;
        $deleteValuationResponse = $this->deleteValuationAPI($oldCarValue, $request->car_value, $carQuote->uuid);

        if ($deleteValuationResponse) {
            $carQuote->save();

            // Call capi api to verify OCR data
            $this->verifyOCRData($carQuote);

            $carQuoteDetails = CarQuoteRequestDetail::where('car_quote_request_id', $carQuote->id)->first();
            $carQuoteDetails->chassis_number = $request->chassis_number;

            if ($carQuoteDetails->isDirty()) {
                $carQuoteDetails->chassis_number = $request->chassis_number;
                $carQuoteDetails->save();
            }

            if (isset($request->gender)) {
                Customer::where('id', $carQuote->customer_id)->update(['gender' => $request->gender]);
            }

            $oldFormattedDate = ! empty($oldDob) ? $oldDob->format('Y-m-d') : '';
            // update embedded products list
            if (
                (isset($request->dob) && $oldFormattedDate != $request->dob) ||
                (isset($request->vehicle_type_id) && $oldBodyType != $request->vehicle_type_id)
            ) {
                Ken::request('/save-embedded-transaction', 'post', ['quoteUID' => $id, 'quoteTypeId' => QuoteTypeId::Car]);
            }

            if (isset($request->return_to_view)) {
                return redirect('quote/car/'.$carQuote->id)->with('success', 'Car Quote has been updated');
            }
        } else {
            return false;
        }
    }

    public function verifyOCRData(CarQuote $carQuote): void
    {
        // Get OCR enabled status
        $isOCREnabled = getAppStorageValueByKey(ApplicationStorageEnums::OCR_ENABLED, useCache: true) == '1';

        // Check if OCR is enabled and has OCR data
        if ($isOCREnabled && $this->hasOCRData($carQuote->id, QuoteTypes::CAR->modelClass())) {
            // Send request to Capi to verify documents
            $requestData = [
                'quoteUuid' => $carQuote->uuid,
                'quoteTypeId' => QuoteTypes::getId(QuoteTypes::CAR),
                'callSource' => LeadSourceEnum::IMCRM,
            ];

            LoggerService::info('Capi service request data', extra: $requestData);
            $response = $this->capiService->request('/api/customer/documents-verify', 'PUT', $requestData);
            LoggerService::info('Capi service response', extra: [
                'response' => $response,
            ]);
        }
    }

    public function getEntity($id)
    {
        return DB::table('car_quote_request as cqr')
            ->select(
                'cqr.uuid',
                'cqr.id',
                'cqr.first_name',
                'cqr.last_name',
                'cqr.driver_name',
                DB::raw('CONCAT(cqr.first_name, " ", cqr.last_name) as full_name'),
                'cqr.company_name AS car_company_name',
                'cqr.company_address AS car_company_address',
                DB::raw('DATE_FORMAT(cqr.dob, "%d-%m-%Y") as dob'),
                'cqr.car_value',
                'cqr.additional_notes',
                'cqr.nationality_id',
                'cqr.year_of_manufacture',
                'cqr.code',
                'cqr.is_ecommerce',
                'cqr.sic_advisor_requested',
                'cqr.premium',
                DB::raw('DATE_FORMAT(cqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
                'cqr.payment_gateway',
                'cqr.source',
                DB::raw('DATE_FORMAT(cqr.created_at, "%d-%m-%Y %H:%i:%s") as created_at'),
                DB::raw('DATE_FORMAT(cqr.updated_at, "%d-%m-%Y %H:%i:%s") as updated_at'),
                'cqr.seat_capacity',
                'cqr.cylinder',
                'cqr.vehicle_type_id',
                'n.TEXT AS nationality_id_text',
                'cqr.promo_code',
                'cqr.device',
                'cqr.policy_number',
                'cqr.previous_quote_id',
                'cqr.renewal_batch',
                'cqr.policy_expiry_date',
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
                'cqr.previous_advisor_id',
                'u.name AS advisor_id_text',
                'pra.name AS previous_advisor_id_text',
                'cqr.payment_status_id',
                'ps.text AS payment_status_id_text',
                'cqr.price_vat_not_applicable',
                'cqr.price_vat_applicable',
                'cqr.price_with_vat',
                'cqr.vat',
                'cqr.insurer_quote_number',
                'cqr.policy_issuance_status_id',
                'cqr.policy_issuance_status_other',
                'cqr.plan_id',
                'cqr.payment_paid_at',
                'cp.text AS plan_id_text',
                'cp.provider_id AS car_plan_provider_id',
                'cpip.text AS car_plan_provider_id_text',
                'cqr.quote_status_id',
                'qs.text AS quote_status_id_text',
                'cqr.year_of_manufacture AS year_of_manufacture_text',
                DB::raw('DATE_FORMAT(cqrd.next_followup_date, "%d-%m-%Y %H:%i:%s") as next_followup_date'),
                'cqrd.transapp_code',
                'cqrd.notes',
                'cqrd.lost_approval_status',
                'cqrd.lost_approval_reason',
                'cqrd.insly_id',
                'vt.text as vehicle_type_id_text',
                'cqr.currently_insured_with',
                'cqr.currently_insured_with as currently_insured_with_text',
                'ls.text as lost_reason',
                'ls.id as lost_reason_id',
                'cqr.previous_quote_policy_number',
                DB::raw('DATE_FORMAT(cqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
                DB::raw('DATE_FORMAT(cqr.previous_policy_start_date, "%d-%m-%Y") as previous_policy_start_date'),
                'cqr.previous_quote_policy_premium',
                'cqr.car_model_detail_id',
                'cmd.text as car_model_detail_id_text',
                'cqr.is_modified',
                'cqr.is_bank_financed',
                'cqr.is_gcc_standard',
                'cqr.current_insurance_status',
                'cqr.year_of_first_registration',
                'cqr.has_ncd_supporting_documents',
                'cqr.back_home_license_held_for_id',
                'cqr.kyc_decision',
                'ulhfs.TEXT as back_home_license_held_for_id_text',
                'cqr.policy_start_date',
                'cqr.policy_issuance_date',
                'cqr.customer_id',
                'cqr.parent_duplicate_quote_id',
                'cqr.renewal_import_code',
                'cqr.quote_link',
                DB::raw('DATE_FORMAT(cqrd.advisor_assigned_date, "%d-%m-%Y %H:%i:%s") as advisor_assigned_date'),
                DB::raw("DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), cqr.dob)), '%Y') + 0 AS customer_age"),
                'cqr.tier_id',
                't.name as tier_id_text',
                'qvc.visit_count as visit_count',
                't.cost_per_lead as cost_per_lead',
                'cqr.quote_batch_id',
                'lu.text as transaction_type_text',
                'qb.name as quote_batch_id_text',
                'cqr.car_value_tier',
                'cqr.risk_score',
                'cpip.code as plan_provider_code',
                DB::raw('(CASE
                WHEN cqr.assignment_type = 1 THEN "System Assigned"
                WHEN cqr.assignment_type = 2 THEN "System ReAssigned"
                WHEN cqr.assignment_type = 3 THEN "Manual Assigned"
                WHEN cqr.assignment_type = 4 THEN "Manual ReAssigned"
                WHEN cqr.assignment_type = 5 THEN "Bought Lead"
                WHEN cqr.assignment_type = 6 THEN "ReAssigned as Bought Lead" ELSE "" END) as assignment_type'),
                'cpip.code as plan_provider_code',
                DB::raw('COALESCE(insured.customer_type, "'.CustomerTypeEnum::Individual.'") as customer_type'),
                'insured.first_name as insured_first_name',
                'insured.last_name as insured_last_name',
                'insured_kyc.id as insured_kyc_id',
                DB::raw('IF(insured.id_type = "emiratesId", insured.id_number, "") as emirates_id_number'),
                'insured.id_type as insured_id_type',
                'insured.id_number as insured_id_number',
                DB::raw('insured_kyc.id_expiry_date as emirates_id_expiry_date'),
                'c.receive_marketing_updates',
                'qrem.entity_id',
                'ent.code as entity_code',
                'ent.trade_license_no',
                'ent.company_name',
                'ent.company_address',
                'qrem.entity_type_code',
                'ent.industry_type_code',
                'cqr.enquiry_count',
                DB::raw('DATE_FORMAT(py.authorized_at, "%d-%m-%Y") as authorized_at'),
                'cqr.policy_booking_date',
                DB::raw('GROUP_CONCAT(team.name) as team_name'),
                'cqr.insurance_provider_id',
                'cpdip.text as insurer_name',
                DB::raw('DATE_FORMAT(cqr.transaction_approved_at, "%d-%m-%Y %H:%i:%s") as transaction_approved_at'),
                'cqr.insly_migrated',
                'cqr.aml_status',
                'cqr.insurer_aml_status',
                'cqrd.chassis_number',
                'c.gender',
                'c.first_name as customer_first_name',
                'c.last_name as customer_last_name',
                DB::raw('
                    CASE
                        WHEN cqr.insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningPending.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningPending).'"
                        WHEN cqr.insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningCleared.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningCleared).'"
                        WHEN cqr.insurer_aml_status = "'.AMLStatusCode::InsurerAMLScreeningFailed.'" THEN "'.AMLStatusCode::getName(AMLStatusCode::InsurerAMLScreeningFailed).'"
                        WHEN cqr.insurer_aml_status IS NULL THEN "'.AMLStatusCode::InsurerAMLScreeningNA.'"
                        ELSE cqr.insurer_aml_status
                    END AS insurer_aml_status_display
                '),
                'cqr.email',
                'cqr.mobile_no',
                'cqr.registration_type',
                'cqr.vehicle_use',
                'cqr.business_activity_id',
                'c.pcp_tag',
                'cqr.pc_qualified',
                DB::raw(Customer::formattedPcpTagCase().' as pcp_tag_formatted'),
                DB::raw(CarQuote::formattedPcQualifiedCase('cqr').' as pc_qualified_formatted'),
                'cqr.api_issuance_status_id',
                'cqr.insurer_api_status_id',
                'cqr.rta_upload_status',
                'cqr.documents_verified',
                'cqr.is_customer_data_valid',
                'cqr.certificate_start_date',
                'cqr.certificate_end_date',
                'cqr.sub_source_id',
                'cqr.sub_source_options_id',
                // 'cqr.primary_ref_id',
                'ss.text as sub_source_text',
                'ss.description as sub_source_description',
                'sso.text as sub_source_option_text',
                'sso.description as sub_source_option_description',
                'ub.branch_id as advisor_primary_branch_id',
                'b.name as lead_branch_name',
                'b.id as lead_branch_id',
                'cqr.is_branch_applicable',
                'vdd.driver_eid_number',
                'vdd.driver_gender',
                'cqrd.is_update_quote_ready',
                'cqrd.engagement_level',
                DB::raw('DATE_FORMAT(cqrd.engagement_level_updated_at, "%d-%m-%Y %H:%i:%s") as engagement_level_updated_at'),
            )
            ->leftJoin('payments as py', function ($join) {
                $join->on('py.paymentable_id', '=', 'cqr.id')
                    ->where('py.paymentable_type', '=', CarQuote::class);
            })
            ->leftJoin('nationality as n', 'n.id', '=', 'cqr.nationality_id')
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'cqrd.lost_reason_id')
            ->leftJoin('car_make as cmake', 'cmake.id', '=', 'cqr.car_make_id')
            ->leftJoin('uae_license_held_for as ulhf', 'ulhf.id', '=', 'cqr.uae_license_held_for_id')
            ->leftJoin('uae_license_held_for as ulhfs', 'ulhfs.id', '=', 'cqr.back_home_license_held_for_id')
            ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'cqr.car_model_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'cqr.transaction_type_id')
            ->leftJoin('lookups as ss', 'ss.id', '=', 'cqr.sub_source_id')
            ->leftJoin('lookups as sso', 'sso.id', '=', 'cqr.sub_source_options_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'cqr.emirate_of_registration_id')
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
            ->leftJoin('claim_history as ch', 'ch.id', '=', 'cqr.claim_history_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
            ->leftJoin('users as pra', 'pra.id', '=', 'cqr.previous_advisor_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('insurance_provider as cpdip', 'cpdip.id', '=', 'cqr.insurance_provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('vehicle_type as vt', 'vt.id', '=', 'cqr.vehicle_type_id')
            ->leftJoin('car_model_detail as cmd', 'cmd.id', '=', 'cqr.car_model_detail_id')
            ->leftJoin('tiers as t', 't.id', '=', 'cqr.tier_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'cqr.quote_batch_id')
            ->leftJoin('customer as c', 'cqr.customer_id', 'c.id')
            ->leftJoin('quote_request_entity_mapping as qrem', function ($entityMappingJoin) {
                $entityMappingJoin->on('qrem.quote_type_id', '=', DB::raw(QuoteTypeId::Car));
                $entityMappingJoin->on('qrem.quote_request_id', '=', 'cqr.id');
            })
            ->leftJoin('entities as ent', 'qrem.entity_id', '=', 'ent.id')
            ->leftJoin('quote_view_count as qvc', function ($join) {
                $join->on('qvc.quote_id', 'cqr.id');
                $join->where('qvc.quote_type_id', QuoteTypeId::Car);
                $join->on('qvc.user_id', 'cqr.advisor_id');
            })
            ->leftJoin('user_team as ut', 'u.id', '=', 'ut.user_id')
            ->leftJoin('teams as team', 'team.id', '=', 'ut.team_id')
            ->leftJoin('customer_insured as ic', function ($insuredCustomerMapping) {
                $insuredCustomerMapping->on('ic.quote_type_id', '=', DB::raw(QuoteTypeId::Car));
                $insuredCustomerMapping->on('ic.quote_request_id', '=', 'cqr.id');
                $insuredCustomerMapping->where('ic.is_active', '=', true);
            })
            ->leftJoin('insured', 'ic.insured_id', '=', 'insured.id')
            ->leftJoin('insured_kyc', 'insured.id', '=', 'insured_kyc.insured_id')
            ->leftJoin('user_branches as ub', function ($join) {
                $join->on('ub.user_id', '=', 'cqr.advisor_id')
                    ->where('ub.is_primary', '=', 1)
                    ->where('ub.status', '=', 1);
            })
            ->leftJoin('branches as b', 'b.id', '=', 'cqr.branch_id')
            ->leftJoin('vehicle_driver_details as vdd', function ($join) {
                $join->on('vdd.quoteable_id', '=', 'cqr.id')
                    ->where('vdd.quoteable_type', '=', CarQuote::class);
            })
            ->when(auth()->user()?->can(PermissionsEnum::VIEW_UTM_SECTION), function ($q) {
                $q->leftJoin('personal_quotes as pq', function ($join) {
                    $join->on('pq.uuid', '=', 'cqr.uuid')
                        ->where('pq.quote_type_id', '=', QuoteTypeId::Car);
                })
                    ->leftJoin('personal_quote_details as pqd', 'pqd.personal_quote_id', '=', 'pq.id')
                    ->addSelect(
                        'pqd.utm_source',
                        'pqd.utm_medium',
                        'pqd.utm_campaign',
                        'pqd.utm_content',
                        'pqd.utm_term',
                    );
            })
            ->groupBy('cqr.id')
            ->where('cqr.uuid', $id)
            ->first();
    }

    public function updateChildRecord($id)
    {
        $childRecord = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();
        $oldAdvisorAssignedDate = $childRecord->advisor_assigned_date ?? null;

        $upsertRecord = CarQuoteRequestDetail::updateOrCreate(
            ['car_quote_request_id' => $id],
            [
                'advisor_assigned_date' => Carbon::now(),
                'advisor_assigned_by_id' => Auth::user()->id,
            ]
        );

        LoggerService::info('updateChildRecord - leadId : '.$id.' - CarQuoteRequestDetail - created: '.$upsertRecord->wasRecentlyCreated);

        return $oldAdvisorAssignedDate;
    }

    public function updatedAccessAgainstPaymentStatus($paymentEntityModel, $record)
    {
        $carPayment = [];
        $paymentStatuses = [
            PaymentStatusEnum::NEW,
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::DECLINED,
            PaymentStatusEnum::AUTHORISED,
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::PARTIALLY_PAID,
            PaymentStatusEnum::OVERDUE,
            PaymentStatusEnum::CREDIT_APPROVED,
            PaymentStatusEnum::CANCELLED,
            PaymentStatusEnum::REFUNDED,
            PaymentStatusEnum::DISPUTED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::DRAFT,
            PaymentStatusEnum::PAYMENT_LINK_REQUESTED,
        ];

        if ($paymentEntityModel->payments) {
            $carPayment = $paymentEntityModel->payments()->where('code', '=', $record->code)->first();
        }

        $access['carAdvisorCanEdit'] = false;
        $access['carManagerCanEdit'] = false;
        $access['carAdvisorCanEditPaymentCancelledRefund'] = false;
        $access['carAdvisorCanEditInsurer'] = false;
        $access['carManagerCanEditInsurer'] = false;

        // Car Advisor Validations
        if (auth()->user()->hasRole(RolesEnum::CarAdvisor) && ! empty($record->payment_status_id)) {
            if ($record->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                $access['carAdvisorCanEditInsurer'] = true;
            }

            if (in_array($record->payment_status_id, [PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED])) {
                $access['carAdvisorCanEditPaymentCancelledRefund'] = true;
            }

            if (
                in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) &&
                $record->quote_status_id !== QuoteStatusEnum::PolicyIssued
            ) {
                $access['carAdvisorCanEdit'] = true;
                $access['carAdvisorCanEditInsurer'] = true;
            }
        }

        // Car Manager Validations
        if (auth()->user()->hasRole(RolesEnum::CarManager) && ! empty($record->payment_status_id)) {
            if ($record->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                $access['carManagerCanEditInsurer'] = true;
            }

            if (
                in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) &&
                $record->quote_status_id !== QuoteStatusEnum::PolicyIssued
            ) {
                $access['carManagerCanEdit'] = true;
                $access['carManagerCanEditInsurer'] = true;
            }
        }

        if (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarAdvisor]) && $record->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
            if ((! empty($record->payment_status_id) && in_array($record->payment_status_id, $paymentStatuses)) || $record->payment_status_id == '' || $record->payment_status_id == null) {
                $access['carManagerCanEdit'] = true;
                $access['carAdvisorCanEdit'] = true;
            }
        }

        return $access;
    }

    public function getSelectedLostReason($id)
    {
        $entity = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();
        $lostId = 0;
        if (! is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }

        return $lostId;
    }

    public function getDetailEntity($id)
    {
        return CarQuoteRequestDetail::firstOrCreate(
            ['car_quote_request_id' => $id],
        );
    }

    public function getEntityPlain($id)
    {
        return CarQuote::where('id', $id)->with([
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
                            'paymentCharges',
                        ]);
                        $paymentSplit->orderBy('sr_no');
                    },
                ]);
                $payment->orderBy('created_at');
            },
        ])->first();
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'code' => 'input|title|ss:0',
            'quote_batch_id' => 'select|readonly|title',
            'renewal_batch' => 'input|number|title|ss:14',
            'first_name' => 'input|text|required|ss:1',
            'last_name' => 'input|text|required|ss:2',
            'dob' => 'input|text|title|required',
            'company_name' => 'input|text|max:250',
            'company_address' => 'input|text|max:1000',
            'customer_age' => 'readonly|none',
            'mobile_no' => 'input|title|number|required|ss:4',
            'email' => 'input|email|required|ss:3',
            'source' => 'input|title|text',
            'nationality_id' => 'select|title|required',
            'uae_license_held_for_id' => 'select|title|required',
            'back_home_license_held_for_id' => 'select|title',
            'car_make_id' => 'select|title|required',
            'car_model_id' => 'select|title|required',
            'cylinder' => 'input|number|title|required',
            'trim' => 'select|title',
            'car_model_detail_id' => 'select|title',
            'year_of_manufacture' => 'select|title|required',
            'year_of_first_registration' => 'input|date|title|range',
            'car_value' => 'input|number',
            'car_value_tier' => 'input|number|title|required',
            'vehicle_type_id' => 'select|title|required|ss:11',
            'seat_capacity' => 'input|number|title|required',
            'emirate_of_registration_id' => 'select|title|required',
            'car_type_insurance_id' => 'select|title|required|ss:12',
            'currently_insured_with' => 'select|title|required|idAsText|ss:13',
            'claim_history_id' => 'select|title|required',
            'has_ncd_supporting_documents' => '|static|title|,Yes,No',
            'created_at' => 'input|date|title|range|ss:5',
            'advisor_assigned_date' => 'input|date|title|range|ss:6',
            'cost_per_lead' => 'readonly|title|none',
            'quote_status_id' => 'select|title|multiple|ss:9',
            'payment_status_id' => 'select|title|ss:7',
            'is_ecommerce' => '|static|title|ss:8|Yes,No',
            'tier_id' => 'select|title|multiple|ss:10',
            'assignment_type' => 'input|title|none',
            'visit_count' => 'readonly|none',
            'next_followup_date' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'updated_by' => 'readonly|none',
            'additional_notes' => 'textarea|',
            'advisor_id' => 'select|title||multiple|ss:17',
            'policy_number' => 'input|text|title',
            'policy_expiry_date' => 'input|date|title|range|ss:15',
            'is_gcc_standard' => '|static|title|Yes,No',
            'is_modified' => '|static|title|Yes,No',
            'premium' => 'input|number',
            'previous_quote_policy_premium' => 'input|title|number',
            'lost_reason' => 'input|text',
            'quote_link' => 'readonly|none',
            'transapp_code' => 'readonly|none',
            'paid_at' => 'input|date',
            'payment_gateway' => 'input|title',
            'promo_code' => 'input|title',
            'device' => 'input|title',
            'previous_quote_id' => 'input|text|title',
            'order_reference' => 'input',
            'payment_reference' => 'input',
            'calculated_value' => 'input|number',
            'created_by' => 'input',
            'plan_id' => 'select|title',
            'car_plan_provider_id' => 'select|title',
            'parent_duplicate_quote_id' => 'input|title',
            'renewal_import_code' => 'input|text',
            'quote_link' => 'readonly|none',
            'previous_quote_policy_number' => 'input|text|title|ss:16',
            'policy_start_date' => 'input|text',
            'previous_policy_expiry_date' => 'input|date|title|range',
            'quote_batch_id' => 'select|title||multiple|ss:0',
            'show_renewal_upload_leads' => '|static|title|ss:18|Yes,No',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'advisor_assigned_date':
                $title = 'Advisor Assigned Date';
                break;
            case 'is_gcc_standard':
                $title = 'Is GCC Standard';
                break;
            case 'dob':
                $title = 'Date of Birth';
                break;
            case 'year_of_first_registration':
                $title = 'First Registration Date';
                break;
            case 'currently_insured_with':
                $title = 'Currently Insured With';
                break;
            case 'uae_license_held_for_id':
                $title = 'UAE licence held for';
                break;
            case 'car_make_id':
                $title = 'Car Make';
                break;
            case 'car_model_id':
                $title = 'Car Model';
                break;
            case 'trim':
            case 'car_model_detail_id':
                $title = 'Trim';
                break;
            case 'nationality_id':
                $title = 'Nationality';
                break;
            case 'mobile_no':
                $title = 'Phone Number';
                break;
            case 'updated_at':
                $title = 'Last Modified Date';
                break;
            case 'quote_status_id':
                $title = 'Lead Status';
                break;
            case 'emirate_of_registration_id':
                $title = 'Emirate Of Registration';
                break;
            case 'car_type_insurance_id':
                $title = 'Type of Car Insurance';
                break;
            case 'claim_history_id':
                $title = 'Claim History';
                break;
            case 'code':
                $title = 'Ref-ID';
                break;
            case 'advisor_id':
                $title = 'Advisor';
                break;
            case 'payment_status_id':
                $title = 'Payment Status';
                break;
            case 'plan_id':
                $title = 'Plan Name';
                break;
            case 'car_plan_provider_id':
                $title = 'Provider Name';
                break;
            case 'is_ecommerce':
                $title = 'Ecommerce';
                break;
            case 'created_at':
                $title = 'Created Date';
                break;
            case 'payment_gateway':
                $title = 'Payment Method';
                break;
            case 'promo_code':
                $title = 'Advisor/Promo Code';
                break;
            case 'device':
                $title = 'Device';
                break;
            case 'year_of_manufacture':
                $title = 'Car Model Year';
                break;
            case 'vehicle_type_id':
                $title = 'Vehicle Type';
                break;
            case 'previous_quote_id':
                $title = 'Previous Quote ID';
                break;
            case 'renewal_batch':
                $title = 'Renewal Batch #';
                break;
            case 'policy_expiry_date':
                $title = 'Policy Expiry Date';
                break;
            case 'policy_number':
                $title = 'Policy Number';
                break;
            case 'next_followup_date':
                $title = 'Follow up date';
                break;
            case 'seat_capacity':
                $title = 'Seat Capacity';
                break;
            case 'cylinder':
                $title = 'Cylinder';
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
            case 'back_home_license_held_for_id':
                $title = 'Home country driving license held for';
                break;
            case 'has_ncd_supporting_documents':
                $title = 'Can you provide no-claims letter from your previous insurers?';
                break;
            case 'parent_duplicate_quote_id':
                $title = 'Parent Ref-ID';
                break;
            case 'quote_link':
                $title = 'Quote Link';
                break;
            case 'is_modified':
                $title = 'Is Vehicle Modified';
                break;
            case 'tier_id':
                $title = 'Tier Name';
                break;
            case 'source':
                $title = 'Lead Source';
                break;
            case 'quote_batch_id':
                $title = 'Batch';
                break;
            case 'car_value_tier':
                $title = 'Car Value (at enquiry)';
                break;
            case 'cost_per_lead':
                $title = 'Lead Cost';
                break;
            case 'show_renewal_upload_leads':
                $title = 'Show Renewal Upload';
                break;
            case 'customer_type':
                $title = 'Customer Type';
                break;
            case 'assignment_type':
                $title = 'Assignment Type';
                break;
            default:
                break;
        }

        return $title;
    }

    public function walkTree($userId, $requestParams = [])
    {
        $user = $requestParams['user'] ?? auth()->user();

        $carTeam = $this->getProductByName(quoteTypeCode::Car);
        array_push($this->childUserIds, $userId);
        if ($user->hasAnyRole([RolesEnum::CarManager, RolesEnum::LeadPool])) {
            $userAllTeams = DB::table('teams')
                ->join('user_team', 'user_team.team_id', 'teams.id')
                ->where('user_id', $userId)
                ->where('teams.parent_team_id', $carTeam->id)->select('teams.id');
            $teamMates = DB::table('user_team')->whereIn('team_id', $userAllTeams)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                array_push($this->childUserIds, $teamMateId);
            }
        } else {
            $carUserIds = $this->getUsersByTeamId($carTeam->id)->pluck('id');
            $teamMates = DB::table('user_manager')->where('manager_id', $userId)->whereIn('user_id', $carUserIds)->pluck('user_id');
            foreach ($teamMates as $teamMateId) {
                $nextChild = DB::table('user_manager')->where('manager_id', $teamMateId)->whereIn('user_id', $carUserIds)->pluck('user_id');
                if (count($nextChild) > 0) {
                    $this->walkTree($teamMateId);
                }
                array_push($this->childUserIds, $teamMateId);
            }
        }
    }

    public function getGridData($model = null, $requestParams = [])
    {
        $user = null;

        if (auth()->check() && empty($requestParams['user'])) {
            $user = auth()->user();
        } elseif (! empty($requestParams['user'])) {
            /* For queue when session data isn't present */
            $user = $requestParams['user'];
        }

        return $this->carQuoteQueryBuilder->processGridData($requestParams)
            ->where(function ($query) use ($user, $requestParams) {
                if ($user->hasRole(RolesEnum::CarManager)) {
                    $this->walkTree($user->id, $requestParams);
                    $query->whereIn('advisor_id', $this->childUserIds);
                } elseif ($user->hasRole(RolesEnum::LeadPool)) {
                    $this->walkTree($user->id, $requestParams);
                    $query->whereIn('advisor_id', $this->childUserIds)->orWhereNull('advisor_id');
                } elseif ($user->hasRole(RolesEnum::CarAdvisor)) {
                    $query->where('advisor_id', $user->id);
                }
            });
    }

    public function postProcessCarQuotes($quotes)
    {
        return $quotes->map(function ($quote) {
            $quote->branch_name = ! $quote->is_branch_applicable ? 'N/A' : ($quote?->branch?->name ?? app(BranchAssignmentService::class)->getBranchName($quote?->advisor?->primaryBranch?->branch_id, QuoteTypeId::Car));

            return $quote;
        });
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('car_quote_request as cqr')
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
            ->leftJoin('teams as team', 'u.team_id', '=', 'team.id')
            ->orderBy('advisor_id', 'ASC');

        if (! empty($CDBID)) {
            $query->where('cqr.CDBID', $CDBID);
        }
        if (! empty($email)) {
            $query->where('cqr.email', $email);
        }
        if (! empty($mobile_no)) {
            $query->where('cqr.mobile_no', $mobile_no);
        }

        return $query;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => 'is_modified,is_gcc_standard,year_of_first_registration,parent_duplicate_quote_id,id,advisor_id,paid_at,lost_reason,payment_status_id,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,policy_expiry_date,renewal_batch,premium,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code,customer_age,tier_id,visit_count,cost_per_lead,quote_batch_id,policy_start_date,advisor_assigned_date,car_value,show_renewal_upload_leads',
            'list' => 'policy_start_date,transapp_code,seat_capacity,cylinder,has_ncd_supporting_documents,back_home_license_held_for_id,parent_duplicate_quote_id,trim,email,mobile_no,paid_at,plan_id,car_plan_provider_id,payment_gateway,promo_code,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,emirate_of_registration_id,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code,customer_age,renewal_batch,show_renewal_upload_leads',
            'update' => 'is_modified,is_gcc_standard,year_of_first_registration,parent_duplicate_quote_id,id,advisor_id,paid_at,policy_expiry_date,payment_status_id,lost_reason,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,source,transapp_code,previous_quote_policy_premium,quote_status_id,car_model_detail_id,renewal_import_code,customer_age,tier_id,visit_count,cost_per_lead,quote_batch_id,policy_start_date,advisor_assigned_date,show_renewal_upload_leads,renewal_batch,previous_quote_policy_number,previous_policy_expiry_date',
            'show' => 'is_modified,is_gcc_standard,trim,previous_quote_id,plan_id,premium,payment_status_id,paid_at,car_plan_provider_id,payment_gateway,quote_status_id,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,renewal_import_code,tier_id,visit_count,id,renewal_batch,policy_start_date,is_ecommerce,quote_link,transapp_code,order_reference,payment_reference,policy_number,policy_expiry_date,lost_reason,show_renewal_upload_leads',
        ];
    }

    public function fillModelSearchProperties()
    {
        $searchProperties = ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'currently_insured_with', 'policy_expiry_date', 'is_ecommerce', 'payment_status_id', 'renewal_batch', 'car_type_insurance_id', 'vehicle_type_id', 'advisor_assigned_date', 'tier_id', 'quote_batch_id', 'advisor_id', 'show_renewal_upload_leads', 'assignment_type', 'registration_type', 'vehicle_use', 'company_name'];

        return $searchProperties;
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'vehicle_type_id', 'policy_expiry_date', 'is_ecommerce', 'payment_status_id', 'renewal_batch', 'car_type_insurance_id', 'currently_insured_with', 'previous_quote_policy_number'];
        $model->renewalSkipProperties = [
            'create' => 'policy_start_date,parent_duplicate_quote_id,id,advisor_id,paid_at,policy_expiry_date,renewal_batch,lost_reason,payment_status_id,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,premium,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code',
            'list' => 'policy_start_date,parent_duplicate_quote_id,trim,additional_notes,email,mobile_no,paid_at,plan_id,car_plan_provider_id,payment_gateway,promo_code,source,seat_capacity,cylinder,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,nationality_id,dob,year_of_manufacture,uae_license_held_for_id,car_value,emirate_of_registration_id,claim_history_id,transapp_code,is_ecommerce,payment_status_id,policy_number,policy_expiry_date,premium,car_model_detail_id,renewal_import_code',
            'update' => 'parent_duplicate_quote_id,id,advisor_id,paid_at,payment_status_id,lost_reason,plan_id,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,policy_expiry_date,source,transapp_code,quote_status_id,renewal_batch,policy_number,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code',
            'show' => 'trim,device,previous_quote_id,plan_id,premium,payment_status_id,paid_at,car_plan_provider_id,payment_gateway,cylinder,seat_capacity,quote_status_id,vehicle_type_id',
        ];
    }

    /**
     * get car quote details, quote plans and pdf.
     *
     * @return mixed
     */
    public function getOcbDetails($uuid)
    {
        $carQuote = CarQuote::select(
            [
                'id',
                'code',
                'uuid',
                'advisor_id',
                'first_name',
                'last_name',
                'email',
                'car_make_id',
                'customer_id',
                'car_model_id',
                'currently_insured_with',
                'quote_status_id',
                'payment_status_id',
                'policy_number',
                'policy_expiry_date',
                'previous_quote_policy_number',
                'previous_policy_expiry_date',
            ]
        )->with(['advisor', 'carMake', 'carModel', 'latestUpdateRenewalQuoteProcess', 'customer' => function ($q) {
            $q->select('id', 'first_name', 'last_name', 'pcp_tag')->with(['additionalContacts' => function ($q) {
                $q->where('key', 'email');
            }]);
        }])
            ->where('uuid', $uuid)->first();

        $renewalsUploadService = app(RenewalsUploadService::class);
        $latestUpdateRenewalQuoteProcess = $carQuote->latestUpdateRenewalQuoteProcess;
        $isTransitionableLead = $latestUpdateRenewalQuoteProcess
            ? $renewalsUploadService->isTransitionableLeadWithCurrentData($latestUpdateRenewalQuoteProcess)
            : false;
        $isRenewalHistorical = $renewalsUploadService->resolveIsRenewalHistorical($carQuote, $isTransitionableLead);

        $plans = $this->getPlans($carQuote->uuid, true, true, true, $isRenewalHistorical);

        $totalPlans = is_countable($plans) ? count($plans) : 0;

        $carQuote->quote_type_id = QuoteTypeId::Car;

        if ($totalPlans > 0) {
            $pdfData = [
                'plan_ids' => collect($plans)->take(5)->pluck('id')->toArray(),
                'quote_uuid' => $carQuote->uuid,
            ];

            $pdf = $this->exportPlansPdf(quoteTypeCode::Car, $pdfData, json_decode(json_encode(['quotes' => ['plans' => $plans], 'isDataSorted' => true])));
            if (isset($pdf['error'])) {
                LoggerService::info('Failed to generate PDF for UUID: '.$carQuote->uuid.' Error: '.$pdf['error']);
            } else {
                $carQuote->pdf = (object) [
                    'content' => base64_encode(($pdf['pdf'])->stream()),
                    'file_name' => $pdf['name'],
                ];
            }
        }

        if ($latestUpdateRenewalQuoteProcess && $latestUpdateRenewalQuoteProcess->data) {
            $carQuote->isTransitionableLead = $isTransitionableLead;
        }

        $carQuote->plans = $plans;

        return $carQuote;
    }

    public function getQuotePlans($id, $isRenewalSort = false, $getLatestRating = false, $isDisabledEnabled = false, $useKen2Endpoint = false, $isRenewalHistorical = false, $process = '')
    {
        $quoteUuId = CarQuote::where('uuid', '=', $id)->value('uuid');

        if (! $quoteUuId) {
            LoggerService::warning(self::class.' - FN: getQuotePlans - CarQuote not found for UUID: '.$id);

            return 'Quote Not Found!';
        }

        if ($useKen2Endpoint) {
            $plansApiEndPoint = config('constants.KEN2_API_ENDPOINT').'/get-car-quote-plans';
        } else {
            $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-car-quote-plans';
        }
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'getLatestRating' => $getLatestRating,
            'lang' => 'en',
            'url' => strval(url()->current()),
            'ipAddress' => request()->ip(),
            'userAgent' => request()->header('User-Agent'),
            'userId' => strval(auth()->id()),
            'filters' => [[
                'field' => 'isRenewalSort',
                'value' => $isRenewalSort,
            ]],
            'isRenewalHistorical' => $isRenewalHistorical,
            'callProcess' => $process,
            'callSource' => 'imcrm',
        ];

        if ($isDisabledEnabled) {
            $plansDataArr['filters'][] = [
                'field' => 'isDisabled',
                'value' => false,
            ];
        }

        $client = new Client;

        try {
            LoggerService::info('Calling KEN get-car-quote-plans to update plans', ['quote_uuid' => $quoteUuId, 'data' => $plansDataArr]);
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'x-api-token' => $plansApiToken,
                        'Authorization' => 'Basic '.$authBasic,
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
        } catch (BadResponseException $e) {

            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            if (strpos($contents, 'Genesis') !== false || strpos($contents, 'Phoenix') !== false) {
                LoggerService::warning('FN: getQuotePlans KEN Transition Error - UUID: '.$quoteUuId.' - Response Error: '.$contents.' - '.$e->getMessage());
            } else {
                LoggerService::error('FN: getQuotePlans KEN Error - UUID: '.$quoteUuId.' - Response Error: '.$contents.' - '.$e->getMessage());
            }

            if (isset($response->message)) {
                $responseBodyAsString = $response->message;
            } elseif (isset($response->error)) {
                $responseBodyAsString = $response->error;
            } elseif (isset($response->msg)) {
                $responseBodyAsString = $response->msg;
            } else {
                $responseBodyAsString = 'Quote unavailable for the selected location and region. Please call 800 ALFRED.';
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
                // 'car_addon_option.vat AS car_addon_option_vat',
                'car_quote_request_addon.price AS car_quote_request_addon_price',
                'car_addon.type AS car_addon_type'
            )
            ->leftJoin('car_addon', 'car_addon.id', '=', 'car_addon_option.addon_id')
            ->leftJoin('car_quote_request_addon', 'car_addon_option.id', '=', 'car_quote_request_addon.addon_option_id')
            ->leftJoin('car_quote_request', 'car_quote_request.id', '=', 'car_quote_request_addon.quote_request_id')
            ->where('car_quote_request.uuid', $id)->get();

        return $listCarQuotePlanAddons;
    }

    /**
     * modify plan during upload & update process.
     *
     * @param  $data
     * @return false
     */
    public function renewalCreatePlan($planData)
    {
        $apiCreds = [
            'apiEndPoint' => config('constants.KEN2_API_ENDPOINT').'/save-manual-car-quote-plan',
            'apiToken' => config('constants.KEN_API_TOKEN'),
            'apiTimeout' => config('constants.KEN_API_TIMEOUT'),
            'apiUserName' => config('constants.KEN_API_USER'),
            'apiPassword' => config('constants.KEN_API_PWD'),
        ];

        return $this->httpService->processRequest($planData, $apiCreds);
    }

    public function carPlanModify($request)
    {
        if (($response = $this->isPlanModifyAllowed($request->all())) === true) {
            $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-car-quote-plan';
            $apiToken = config('constants.KEN_API_TOKEN');
            $apiTimeout = config('constants.KEN_API_TIMEOUT');
            $apiUserName = config('constants.KEN_API_USER');
            $apiPassword = config('constants.KEN_API_PWD');

            if (isset($request->is_create)) {
                if ($request->is_create == 1) {
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
            if ($request->addons != null && count($request->addons) > 0) {
                $addons = $request->addons;
            } else {
                $addons = [];
            }

            $carPlanData = [
                'quoteUID' => $request->car_quote_uuid,
                'update' => $isUpdate,
                'url' => strval($request->current_url),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->header('User-Agent'),
                'userId' => strval(auth()->id()),
                'plans' => [
                    [
                        'planId' => (int) $request->car_plan_id,
                        'actualPremium' => (float) $request->actual_premium,
                        'carValue' => (float) $request->car_value,
                        'excess' => (float) $request->excess,
                        'discountPremium' => (float) $discountedPremium,
                        'isDisabled' => isset($request->is_disabled) ? (bool) $request->is_disabled : (bool) false,
                        'addons' => $addons,
                        'insurerTrimId' => strval($request->insurerTrim),
                        'insurerQuoteNo' => strval($request->insurer_quote_no),
                        'isManualUpdate' => $request->is_manual_update,
                        'ancillaryExcess' => (int) $request->ancillary_excess,
                    ],
                ],
            ];

            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            $response = $this->httpService->processRequest($carPlanData, $apiCreds);
            if ($response == 200) {
                $plan_id = CarQuote::where('uuid', '=', $request->car_quote_uuid)->value('plan_id');
                if ($plan_id == $request->car_plan_id) {
                    $request->merge(['plan_id' => $request->car_plan_id]);
                    (new CentralService)->updateSelectedPlan(quoteTypeCode::Car, $request->car_quote_uuid, $request);
                }
                $this->lockCarQuote($request->car_quote_uuid);
            }
        }

        return $response;
    }

    /**
     * @return bool|string
     *                     paid_at = authorized date
     */
    public function isPlanModifyAllowed($data)
    {
        if ($enablePlanValidation = ApplicationStorage::where('key_name', ApplicationStorageEnums::ENABLE_PLAN_MODIFY_VALIDATION)->first()) {
            if (! $enablePlanValidation->value) {
                LoggerService::info('plan modification validation is disabled from backend');

                return true;
            }
        }

        $logPrefix = 'fn: isPlanModifyAllowed ';
        $quote = CarQuote::where('uuid', $data['car_quote_uuid'])->with('paymentStatus')->first();
        $paymentStatuses = [
            PaymentStatusEnum::NEW,
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::DECLINED,
            PaymentStatusEnum::AUTHORISED,
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::PARTIALLY_PAID,
            PaymentStatusEnum::OVERDUE,
            PaymentStatusEnum::CREDIT_APPROVED,
            PaymentStatusEnum::CANCELLED,
            PaymentStatusEnum::REFUNDED,
            PaymentStatusEnum::DISPUTED,
            PaymentStatusEnum::FAILED,
            PaymentStatusEnum::DRAFT,
        ];

        $quoteStatuses = [
            QuoteStatusEnum::NewLead,
            QuoteStatusEnum::PaymentLinkRequestedByCustomer,
            QuoteStatusEnum::PaymentLinkInprogress,
            QuoteStatusEnum::PaymentLinkSentToCustomer,
            QuoteStatusEnum::Quoted,
            QuoteStatusEnum::FollowedUp,
            QuoteStatusEnum::PaymentPending,
            QuoteStatusEnum::SentForTransactionApproval,
            QuoteStatusEnum::TransactionDeclined,
            QuoteStatusEnum::Stale,
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyDocumentsPending,
        ];

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            if (auth()->user()->hasRole(RolesEnum::CarAdvisor) && $quote->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
                info($logPrefix.' plan modify allowed to advisor for uuid '.$quote->uuid);

                return true;
            } elseif (auth()->user()->hasRole(RolesEnum::CarManager) && $quote->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
                info($logPrefix.' plan modify allowed to car manager for uuid '.$quote->uuid);

                return true;
            }
        }

        if (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::CarAdvisor]) && $quote->quote_status_id !== QuoteStatusEnum::PolicyIssued) {
            if (in_array($quote->payment_status_id, $paymentStatuses) || in_array($quote->quote_status_id, $quoteStatuses) || $quote->payment_status_id == '' || $quote->payment_status_id == null) {
                info($logPrefix.' plan modify allowed for uuid '.$quote->uuid);

                return true;
            }
        }

        info($logPrefix.' plan modification is not allowed for uuid '.$quote->uuid);

        return 'Plan Modification is not allowed';
    }

    public function getDuplicateEntityByCode($code)
    {
        return CarQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function getPlans($id, $isRenewalSort = false, $isDisabledEnabled = false, $useKen2Endpoint = false, $isRenewalHistorical = false)
    {
        $quotePlans = $this->getQuotePlans($id, $isRenewalSort, false, $isDisabledEnabled, $useKen2Endpoint, $isRenewalHistorical);

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string' && isset($quotePlans->quotes->plans)) {
                $listQuotePlans = $quotePlans->quotes->plans;
            } elseif (! isset($quotePlans->quotes->plans)) {
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

    public function processManualLeadAssignment($request): array
    {
        $userId = (int) $request->assigned_to_id_new;
        $quoteType = $request->modelType;

        foreach ($this->getLeadIdsToProcessFromRequest($request) as $leadId) {
            $lead = $this->getEntityPlain($leadId);

            $oldAssignmentType = $lead->assignment_type;

            $isReassignment = $lead->advisor_id != null ? true : false; // checking if the advisor is already assigned or not for reassignment email template

            $previousAdvisorId = $lead->advisor_id; // saving previous advisor before updating the new to update the counts

            $lead->advisor_id = $userId;

            $lead->assignment_type = $isReassignment ? AssignmentTypeEnum::MANUAL_REASSIGNED : AssignmentTypeEnum::MANUAL_ASSIGNED;

            LoggerService::info(self::class.' - processManualLeadAssignment: Checking lead_assignment_trigger', extra: [
                'current_value' => $lead->lead_assignment_trigger ?? 'null',
            ]);
            if (empty($lead->lead_assignment_trigger)) {
                LoggerService::info(self::class.' - processManualLeadAssignment: Setting lead_assignment_trigger to MANUAL_ALLOCATION');
                $lead->lead_assignment_trigger = LeadAssignmentTriggerEnum::MANUAL_ALLOCATION;
            }

            $quoteBatch = QuoteBatches::latest()->first();

            LoggerService::info('About to assign quote batch with id : '.$quoteBatch->id.' and with name : '.$quoteBatch->name.' to quote : '.$lead->uuid);

            $lead->quote_batch_id = $quoteBatch->id;

            $this->updateTierAndCost($lead); // will assign/update tier and update cost per lead from tier

            $oldAdvisorAssignedDate = $this->updateChildRecord($lead->id); // will update the car quote request detail entity about assignment

            LoggerService::info('Manual assignment done for lead : '.$lead->uuid.' and old advisor assigned date is : '.$oldAdvisorAssignedDate);

            $this->addManualAllocationCountAndUpdate($userId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $oldAssignmentType, $quoteType);

            $this->addOrUpdateQuoteViewCount($lead, QuoteTypeId::Car, $userId);
            $lead->auto_assigned = false;

            $lead->save();
        }

        return [];
    }

    public function updateTierAndCost($lead)
    {
        if ($lead->tier_id == null) {
            $selectedTier = $this->leadAllocationService->getTierForValue($lead);

            if ($selectedTier) {
                $lead->tier_id = $selectedTier->id;
                $lead->cost_per_lead = $selectedTier->cost_per_lead;
            } else {
                LoggerService::info('Unable to find tier against lead : '.$lead->code);
            }
        } else {
            $lead->cost_per_lead = Tier::where('id', $lead->tier_id)->get()->first()->cost_per_lead;
        }
    }

    public function getLeadIdsToProcessFromRequest($request)
    {
        if ($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        } else {
            $leadIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }

        return $leadIds;
    }

    public function getEntityPlainByUUID($uuid)
    {
        return CarQuote::where('uuid', $uuid)->first();
    }

    public function validateRequest($request)
    {
        $canReAssignAdvisor = Auth::user()->isLeadPool() || auth()->user()->can(PermissionsEnum::ASSIGN_PAID_LEADS);
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
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && ! $canReAssignAdvisor) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }

        return 'true';
    }

    public function updateManualPlansBulk($request)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-car-quote-plan';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');
        $apiUserName = config('constants.KEN_API_USER');
        $apiPassword = config('constants.KEN_API_PWD');
        if ($request->planIds) {
            $data = $request->planIds;
            $isDisabled = $request->toggle;
            $plansArray = [];
            for ($i = 0; $i < count($data); $i++) {
                $apiArray = [
                    'planId' => (int) $data[$i],
                    'isDisabled' => filter_var($isDisabled, FILTER_VALIDATE_BOOLEAN),
                ];
                array_push($plansArray, $apiArray);
            }

            $dataArray = [
                'quoteUID' => $request->car_quote_uuid,
                'update' => true,
                'plans' => $plansArray,
            ];
            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            $response = $this->httpService->processRequest($dataArray, $apiCreds);

            return $response;
        }
    }

    public function lockCarQuote($quoteUuId)
    {
        $carQuote = CarQuote::where('uuid', $quoteUuId)->first();
        $carQuote->is_quote_locked = true;
        $carQuote->save();

        return $carQuote->id;
    }

    /**
     * generate PDF for car quote plan and return.
     *
     * @return array|string[]
     */
    public function exportPlansPdf($quoteType, $data, $quotePlans = null)
    {
        $planIds = $data['plan_ids'];
        $addons = (isset($data['addons'])) ? $data['addons'] : null;

        if ($quotePlans == null) {
            $quotePlans = $this->getQuotePlans($data['quote_uuid']);
        }

        if (! isset($quotePlans->quotes->plans)) {
            return ['error' => 'Quote plans not available'];
        }

        $quote = $this->getQuoteObjectBy($quoteType, $data['quote_uuid'], 'uuid');

        $quote->load(['carMake', 'carModel', 'advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no', 'profile_photo_path');
        }, 'customer', 'vehicleType']);

        $view = $quote->registration_type == CarRegistrationType::COMPANY ? 'pdf.car_comparision.company_car_pdf' : 'pdf.quote_plans';

        $ecomInsuranceLink = config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$quote->uuid;
        try {
            $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150, 'isPhpEnabled' => true, 'isRemoteEnabled' => true])
                ->loadView($view, compact('quotePlans', 'planIds', 'quote', 'addons', 'ecomInsuranceLink'));
        } catch (\Throwable $e) {
            LoggerService::error('Error generating PDF: '.$e->getMessage(), extra: [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }

        // generate pdf with file name e.g. InsuranceMarket.ae™ Motor Insurance Comparison for Rahul.pdf
        $pdfName = 'InsuranceMarket.ae™ Motor Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }

    private function deleteValuationAPI($oldValue, $currentValue, $quoteUuId)
    {
        if ($oldValue == $currentValue) {
            return true;
        }

        try {
            $deleteValuationAPI = config('constants.KEN_API_ENDPOINT').'/delete-car-valuation';
            $kenCapiBasicAuthUsername = config('constants.KEN_API_USER');
            $kenCapiBasicAuthPassword = config('constants.KEN_API_PWD');
            $kenCapiApiToken = config('constants.KEN_API_TOKEN');

            $response = Http::withHeaders(['x-api-token' => $kenCapiApiToken])
                ->withBasicAuth($kenCapiBasicAuthUsername, $kenCapiBasicAuthPassword)
                ->post($deleteValuationAPI, ['quoteUuid' => $quoteUuId]);

            if ($response->ok()) {
                return true;
            }
        } catch (\Exception $exception) {
            LoggerService::error('Delete Valuation API Error: '.$exception->getMessage());

            return false;
        }
    }

    public function getValidationArray($request): array
    {
        $validationArray = [
            'back_home_license_held_for_id' => 'nullable',
            'year_of_manufacture' => self::REQUIRED,
            'emirate_of_registration_id' => self::REQUIRED,
            'car_type_insurance_id' => self::REQUIRED,
            'claim_history_id' => self::REQUIRED,
            'additional_notes' => 'nullable',
            'car_value_tier' => self::REQUIRED,
            'seat_capacity' => self::REQUIRED,
            'cylinder' => self::REQUIRED_STRING,
            'vehicle_type_id' => self::REQUIRED,
            'car_make_id' => self::REQUIRED, // ID
            'car_model_id' => self::REQUIRED, // ID
            'currently_insured_with' => self::REQUIRED_STRING,
            'chassis_number' => 'nullable|'.self::STRING.'|min:8|max:17|regex:/^[a-zA-Z0-9]+$/',
            'registration_type' => self::REQUIRED,
            // Sub-source validation rules
            'sub_source_id' => 'nullable|integer|exists:lookups,id',
            'sub_source_options_id' => 'nullable|integer|exists:lookups,id',
        ];

        // Sub-source conditional validation
        $isReferralType = $request->input('type') === 'referral' ||
                         ($request->has('source') && $request->source === 'IMCRM');

        if ($isReferralType) {
            // Sub source is required for referral types (except ECOM lead extension)
            $validationArray['sub_source_id'] = 'required|integer|exists:lookups,id';

            // If sub_source_id is provided, validate sub_source_options_id based on available options
            if (! empty($request->sub_source_id) && is_numeric($request->sub_source_id)) {
                // Check if the selected sub-source has child options
                $subSource = Lookup::with('childs')->find($request->sub_source_id);
                if ($subSource && $subSource->childs && $subSource->childs->count() > 0) {
                    $validationArray['sub_source_options_id'] = 'required|integer|exists:lookups,id';
                }
            }
        }

        if ($request->registration_type == CarRegistrationType::COMPANY) {
            $validationArray = array_merge($validationArray, [
                'vehicle_use' => self::REQUIRED_STRING,
                'company_name' => self::REQUIRED_STRING,
                'company_contact_name' => self::REQUIRED_STRING,
                'business_activity_id' => self::REQUIRED,
            ]);

            if ($request->vehicle_use == CarVehicleUse::PRIVATE) {
                $validationArray = array_merge($validationArray, [
                    'driver_name' => self::REQUIRED_STRING.'|between:1,70',
                    'dob' => self::REQUIRED,
                    'nationality_id' => self::REQUIRED,
                    'uae_license_held_for_id' => self::REQUIRED,
                ]);
            }
        } else {
            $validationArray = array_merge($validationArray, [
                'first_name' => 'required|string|between:1,20',
                'last_name' => 'required|string|between:1,50',
                'dob' => 'required',
                'nationality_id' => 'required',
                'uae_license_held_for_id' => 'required',
            ]);
        }

        return $validationArray;
    }

    /**
     * @return bool[]
     */
    public function checkCarLostPermissions($lead, $paymentEntityModel, $statuses)
    {
        $carLostChangeStatus = true;
        $allowQuoteLogAction = true;

        // mo can only change status when status is car sold / uncontactable, based on condition below
        if (! isCarLostStatus($lead->quote_status_id) && auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
            $carLostChangeStatus = false;
            $allowQuoteLogAction = false;
        }

        if (isCarLostStatus($lead->quote_status_id)) {
            // when status is car sold / uncontactable, default lead status change is blocked, will allow agains validations below
            $carLostChangeStatus = false;
            $allowQuoteLogAction = false;

            // validations for Car Advisor / Deputy Manager Role
            if (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor])) {
                $allowQuoteLogAction = false;

                if ($lead->quote_status_id == QuoteStatusEnum::CarSold && $paymentEntityModel?->carLostQuoteLog?->status == GenericRequestEnum::REJECTED && count($paymentEntityModel->carLostQuoteLogs) <= 2) {
                    $carLostChangeStatus = true;
                    $statuses = $statuses->whereIn('id', [$lead->quote_status_id])->all();
                }
            }

            // validations for MO role
            if (auth()->user()->hasRole(RolesEnum::MarketingOperations)) {
                $carLostChangeStatus = false;
                if (isCarLostStatus($lead->quote_status_id) && $paymentEntityModel?->carLostQuoteLog?->status == GenericRequestEnum::PENDING) {
                    $allowQuoteLogAction = true;
                    $statuses = $statuses->whereIn('id', [QuoteStatusEnum::CarSold, QuoteStatusEnum::Uncontactable])->all();
                }
            }
        }

        return [$allowQuoteLogAction, $carLostChangeStatus, $statuses];
    }

    public function addManualAllocationCountAndUpdate($newAdvisorId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $quoteType = null)
    {
        // Check if $lead or $newAdvisorId is not provided
        if ($lead === null || $newAdvisorId === null) {
            LoggerService::error('Lead or new advisor ID is null, unable to update allocation counts');

            return;
        }

        // Skip allocation count updates for IMCRM source leads
        if ($lead->source === LeadSourceEnum::IMCRM) {
            LoggerService::info('Skipping allocation count update for IMCRM source lead: '.$lead->uuid);

            return;
        }

        LoggerService::info('Previous assignment type is : '.$previousAssignmentType);

        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType) ?? null;

        // Constants for system assigned types
        $systemAssignedTypes = [AssignmentTypeEnum::SYSTEM_ASSIGNED, AssignmentTypeEnum::SYSTEM_REASSIGNED, AssignmentTypeEnum::BOUGHT_LEAD, AssignmentTypeEnum::REASSIGNED_AS_BOUGHT_LEAD];

        // Get the allocation record for the new advisor
        $newAdvisorAllocationRecord = $this->leadAllocationService->getLeadAllocationRecordByUserId($newAdvisorId, $quoteTypeId);

        // Update allocation counts for the new advisor only if its different from previous advisor

        if ($newAdvisorId !== $previousAdvisorId) {
            // Update allocation counts for the new advisor (if applicable)
            $this->updateAllocationCountsForNewAdvisor($newAdvisorAllocationRecord, $lead, $systemAssignedTypes);
        }

        // Get the allocation record for the previous advisor (if applicable)
        if ($previousAdvisorId !== null) {
            $previousAdvisorAllocationRecord = $this->leadAllocationService->getLeadAllocationRecordByUserId($previousAdvisorId, $quoteTypeId);

            // Update allocation counts for the previous advisor (if applicable)
            $this->updateAllocationCountsForPreviousAdvisor($previousAdvisorId, $oldAdvisorAssignedDate, $previousAssignmentType, $previousAdvisorAllocationRecord, $systemAssignedTypes);
        }
    }

    public function getExportDataWithPlans(): Collection
    {
        $request = request();
        $results = [];
        DB::table('car_quote_request as q')
            ->leftJoin('nationality as n', 'q.nationality_id', '=', 'n.id')
            ->leftJoin('car_make as cmk', 'q.car_make_id', '=', 'cmk.id')
            ->leftJoin('car_model as cmd', 'q.car_model_id', '=', 'cmd.id')
            ->leftJoin('vehicle_type as vt', 'q.vehicle_type_id', '=', 'vt.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'q.quote_status_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'q.payment_status_id')
            ->leftJoin('emirates as e', 'q.emirate_of_registration_id', '=', 'e.id')
            ->leftJoin('car_quote_plan_details as cqpd', function ($join) {
                $join->on('q.uuid', '=', 'cqpd.quote_uuid')
                    ->whereColumn('q.plan_id', '=', 'cqpd.plan_id');
            })
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'q.plan_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'cp.provider_id')
            ->select(
                'q.code',
                'q.first_name',
                'q.last_name',
                'q.dob',
                'n.text as nationality',
                'cmk.text as car_make',
                'cmd.text as car_model',
                'q.year_of_manufacture',
                'q.car_value',
                'q.car_value_tier',
                'vt.text as vehicle_type',
                'e.text as emirate_of_registration',
                'cqpd.repair_type',
                'cqpd.addons',
                'q.created_at',
                'qs.text as lead_status',
                'ps.text as payment_status',
                'cp.text as plan_name',
                'ip.text as provider_name',
                'q.paid_at'
            )
            ->whereBetween('q.paid_at', [$request->paid_at_start, $request->paid_at_end])
            ->whereIn('q.payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::AUTHORISED])
            ->orderBy('q.created_at')
            ->chunk(500, function ($carQuoteRequestData) use (&$results) {
                foreach ($carQuoteRequestData as $row) {
                    $addons = json_decode($row->addons, true);
                    unset($row->addons);
                    if ($addons) {
                        foreach ($addons as $addon) {
                            $addonName = $addon['text'];
                            foreach ($addon['carAddonOption'] as $option) {
                                $addonValue = $option['value'];
                                $newRow = clone $row;
                                $newRow->add_on_name = $addonName;
                                $newRow->add_on_value = $addonValue;
                                $newRow->is_selected = $option['isSelected'] ? 'Yes' : 'No';
                                $results[] = $newRow;
                            }
                        }
                    }
                }
            });

        return collect($results);
    }

    public function getExportDataWithMobileAndEmail()
    {
        $request = request();
        $results = DB::table('car_quote_request AS cqr')
            ->select(
                'cqr.code',
                'qb.name AS batch_no',
                'cqr.first_name',
                'cqr.last_name',
                'cqr.email',
                'cqr.mobile_no',
                'cqr.created_at',
                'qs.text AS status',
                'tr.name AS tier',
                'u.name AS assigned_to'
            )
            ->leftJoin('quote_status AS qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('users AS u', 'u.id', '=', 'cqr.advisor_id')
            ->leftJoin('quote_batches AS qb', 'qb.id', '=', 'cqr.quote_batch_id')
            ->leftJoin('tiers AS tr', 'tr.id', '=', 'cqr.tier_id')
            ->whereBetween('cqr.created_at', [$request->created_at_start, $request->created_at_end])
            ->orderBy('cqr.created_at', 'ASC')
            ->get();

        return collect($results);
    }

    public function getExportDataWithMakeModelTrim()
    {
        $request = request();
        $results = DB::table('car_make AS cmk')
            ->select(
                'cmk.code AS MakeCode',
                'cmk.text AS make_name',
                'cmd.code',
                'cmd.text AS model_name',
                'cmdd.trim_id AS trim_id',
                'cmdd.text AS trim_name',
                'cmdd.default_trim_id',
                'cmdd.current_value',
                'cmk.axa_car_make',
                'cmk.oman_car_make',
                'cmk.tokio_car_make',
                'cmk.qatar_car_make',
                'cmk.rsa_car_make',
                'cmd.axa_car_model',
                'cmd.oman_car_model',
                'cmd.tokio_car_model',
                'cmd.qatar_car_model',
                'cmd.rsa_car_model',
                'cmdd.axa_model_detail',
                'cmdd.oman_model_detail',
                'cmdd.no_of_doors',
                'cmdd.hp',
                'cmdd.cubic_capacity',
                'cmdd.transmission',
                'cmdd.drive_type',
                'cmdd.seating_capacity',
                'cmdd.cylinder',
                'vt.text AS body_type',
                'cmk.is_active AS make_is_active',
                'cmd.is_active AS mode_is_active',
                'cmdd.is_active AS trim_is_active',
                'cmk.is_deleted AS make_is_deleted',
                'cmd.is_deleted AS model_is_deleted',
                'cmdd.is_deleted AS trim_is_deleted'
            )
            ->leftJoin('car_model AS cmd', 'cmd.car_make_code', '=', 'cmk.code')
            ->leftJoin('car_model_detail AS cmdd', 'cmdd.car_model_id', '=', 'cmd.id')
            ->leftJoin('vehicle_type AS vt', 'vt.id', '=', 'cmdd.vehicle_type_id')
            ->get();

        return collect($results);
    }

    public function exportnonPUAAuthorized($requestParams = [])
    {
        LoggerService::info('CarQuoteService::exportnonPUAAuthorized - Method called for Car PUA export');

        if (! empty($requestParams)) {
            $request = new Request($requestParams);
        } else {
            $request = request();
        }

        LoggerService::info('exportnonPUAAuthorized', ['request_params' => $request->all()]);

        $carTeam = $this->getProductByName(quoteTypeCode::Car);

        $nonPUAAuthLead = DB::table('car_quote_request as q')
            ->select(
                'q.code as RefID',
                'q.premium_authorized as premiumauthorized',
                'q.payment_status_date as paymentauthdate',
                DB::raw('qs.text as `leadstatus`'),
                DB::raw("'AUTHORIZED' as `paymentstatus`"),
                'q.source as source',
                'cmk.text as make',
                'cmd.text as model',
                'u.email as assignedadvisoremail',
                'd.name as departmentname'
            )
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.paymentable_id', '=', 'q.id')
                    ->where('p.paymentable_type', '=', 'App\\Models\\CarQuote');
            })
            ->leftJoin('car_make as cmk', 'q.car_make_id', '=', 'cmk.id')
            ->leftJoin('car_model as cmd', 'q.car_model_id', '=', 'cmd.id')
            ->leftJoin('users as u', 'q.advisor_id', '=', 'u.id')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.id')
            ->join('user_team as ut', 'q.advisor_id', '=', 'ut.user_id')
            ->join('teams as t', 'ut.team_id', '=', 't.id')
            ->join('quote_status as qs', 'q.quote_status_id', '=', 'qs.id')
            ->where('q.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::PolicyIssued])
            ->where('t.parent_team_id', $carTeam->id)
            ->whereNotIn('q.uuid', function ($query) use ($request) {

                $query->select('q.uuid')
                    ->from('car_quote_plan_details as cqp')
                    ->join('car_quote_request as q', 'cqp.quote_uuid', '=', 'q.uuid')
                    ->leftJoin('car_plan as cp', 'q.plan_id', '=', 'cp.id')
                    ->leftJoin('insurance_provider as ip', 'cp.provider_id', '=', 'ip.id')
                    ->where('q.payment_status_id', PaymentStatusEnum::AUTHORISED)
                    ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::PolicyIssued])
                    ->whereNotNull('cqp.pua_premium')
                    ->whereColumn('cqp.plan_id', '=', 'q.plan_id')
                    ->when($request->filled('authorize_date'), function ($subQuery) use ($request) {
                        $authorizeDate = $request->input('authorize_date');
                        if ($authorizeDate) {
                            $endDate = Carbon::parse($authorizeDate)->subDay();
                            $startDate = $endDate->copy()->subDays(30);

                            $subQuery->whereBetween('q.paid_at', [
                                $startDate->startOfDay()->toDateTimeString(),
                                $endDate->endOfDay()->toDateTimeString(),
                            ]);
                        }
                    }, function ($subQuery) {
                        // Default date range when no authorize_date is provided
                        $subQuery->whereRaw('q.paid_at <= DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR')
                            ->whereRaw('q.paid_at > DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY');
                    });
            })
            ->when($request->filled('authorize_date'), function ($query) use ($request) {
                $authorizeDate = $request->input('authorize_date');
                if ($authorizeDate) {
                    $endDate = Carbon::parse($authorizeDate)->subDay();
                    $startDate = $endDate->copy()->subDays(30);

                    $query->whereBetween('q.paid_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);
                    $query->whereBetween('p.authorized_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);
                }
            }, function ($query) {
                // Default date range when no authorize_date is provided
                $query->whereRaw('q.paid_at <= DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR')
                    ->whereRaw('q.paid_at > DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY');
            })
            ->orderBy('q.paid_at', 'desc');

        $nonPUAAuthLead = $nonPUAAuthLead->get();

        $nonPUAAuthTeamCount = DB::table('car_quote_request as q')
            ->select(
                't.name as Team',
                DB::raw('COUNT(*) as Total')
            )
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.paymentable_id', '=', 'q.id')
                    ->where('p.paymentable_type', '=', 'App\\Models\\CarQuote');
            })
            ->leftJoin('users as u', 'q.advisor_id', '=', 'u.id')
            ->join('user_team as ut', 'q.advisor_id', '=', 'ut.user_id')
            ->join('teams as t', 'ut.team_id', '=', 't.id')
            ->where('q.payment_status_id', PaymentStatusEnum::AUTHORISED)
            ->where('t.parent_team_id', $carTeam->id)
            ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::PolicyIssued])
            ->whereNotIn('q.uuid', function ($query) {
                $query->select('q.uuid')
                    ->from('car_quote_plan_details as cqp')
                    ->join('car_quote_request as q', 'cqp.quote_uuid', '=', 'q.uuid')
                    ->whereNotNull('cqp.pua_premium')
                    ->whereColumn('cqp.plan_id', '=', 'q.plan_id');
            })
            ->when($request->filled('authorize_date'), function ($query) use ($request) {
                $authorizeDate = $request->input('authorize_date');
                if ($authorizeDate) {
                    $endDate = Carbon::parse($authorizeDate)->subDay();
                    $startDate = $endDate->copy()->subDays(30);

                    $query->whereBetween('q.paid_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);

                    $query->whereBetween('p.authorized_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);
                }
            }, function ($query) {
                // Default date range when no authorize_date is provided
                $query->whereRaw('q.paid_at <= DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR')
                    ->whereRaw('q.paid_at > DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY');
            })
            ->groupBy('t.name');

        $nonPUAAuthTeamCount = $nonPUAAuthTeamCount->get();

        return [$nonPUAAuthLead, $nonPUAAuthTeamCount];
    }

    public function exportPUAAuthorized($requestParams = [])
    {
        LoggerService::info('CarQuoteService::exportPUAAuthorized - Method called for Car PUA export');

        if (! empty($requestParams)) {
            $request = new Request($requestParams);
        } else {
            $request = request();
        }

        LoggerService::info('exportPUAAuthorized', ['request_params' => $request->all()]);

        $carTeam = $this->getProductByName(quoteTypeCode::Car);

        $puaAuthUpdate = DB::table('car_quote_plan_details as cqp')
            ->select(
                'q.code as RefID',
                'q.premium_authorized as premiumauthorized',
                'q.payment_status_date as paymentauthdate',
                DB::raw('qs.text as `leadstatus`'),
                DB::raw("'AUTHORIZED' as `paymentstatus`"),
                'q.source as source',
                'cmk.text as make',
                'cmd.text as model',
                'u.email as assignedadvisoremail',
                'd.name as departmentname'
            )
            ->join('car_quote_request as q', 'cqp.quote_uuid', '=', 'q.uuid')
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.paymentable_id', '=', 'q.id')
                    ->where('p.paymentable_type', '=', 'App\\Models\\CarQuote');
            })
            ->leftJoin('car_plan as cp', 'q.plan_id', '=', 'cp.id')
            ->leftJoin('insurance_provider as ip', 'cp.provider_id', '=', 'ip.id')
            ->leftJoin('car_make as cmk', 'q.car_make_id', '=', 'cmk.id')
            ->leftJoin('car_model as cmd', 'q.car_model_id', '=', 'cmd.id')
            ->leftJoin('users as u', 'q.advisor_id', '=', 'u.id')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.id')
            ->join('user_team as ut', 'q.advisor_id', '=', 'ut.user_id')
            ->join('teams as t', 'ut.team_id', '=', 't.id')
            ->join('quote_status as qs', 'q.quote_status_id', '=', 'qs.id')
            ->where('q.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::PolicyIssued])
            ->whereNotNull('cqp.pua_premium')
            ->where('cqp.plan_id', '=', DB::raw('q.plan_id'))
            ->where('t.parent_team_id', '=', $carTeam->id)
            ->when($request->filled('authorize_date'), function ($query) use ($request) {
                $authorizeDate = $request->input('authorize_date');
                if ($authorizeDate) {
                    $endDate = Carbon::parse($authorizeDate)->subDay();
                    $startDate = $endDate->copy()->subDays(30);

                    $query->whereBetween('q.paid_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);

                    $query->whereBetween('p.authorized_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);
                }
            }, function ($query) {
                // Default date range when no authorize_date is provided
                $query->where('q.paid_at', '<=', DB::raw('DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR'))
                    ->where('q.paid_at', '>', DB::raw('DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY'));
            })
            ->orderBy('q.paid_at', 'desc');

        $puaAuthUpdate = $puaAuthUpdate->get();

        $puaAuthTeamUpdate = DB::table('car_quote_plan_details as cqp')
            ->select(
                't.name as Team',
                DB::raw('COUNT(*) as Total')
            )
            ->join('car_quote_request as q', 'cqp.quote_uuid', '=', 'q.uuid')
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.paymentable_id', '=', 'q.id')
                    ->where('p.paymentable_type', '=', 'App\\Models\\CarQuote');
            })
            ->leftJoin('users as u', 'q.advisor_id', '=', 'u.id')
            ->join('user_team as ut', 'q.advisor_id', '=', 'ut.user_id')
            ->join('teams as t', 'ut.team_id', '=', 't.id')
            ->where('q.payment_status_id', '=', PaymentStatusEnum::AUTHORISED)
            ->whereNotIn('q.quote_status_id', [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::PolicyIssued])
            ->whereNotNull('cqp.pua_premium')
            ->where('cqp.plan_id', '=', DB::raw('q.plan_id'))
            ->where('t.parent_team_id', '=', $carTeam->id)
            ->when($request->filled('authorize_date'), function ($query) use ($request) {
                $authorizeDate = $request->input('authorize_date');
                if ($authorizeDate) {
                    $endDate = Carbon::parse($authorizeDate)->subDay();
                    $startDate = $endDate->copy()->subDays(30);

                    $query->whereBetween('q.paid_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);

                    $query->whereBetween('p.authorized_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);
                }
            }, function ($query) {
                // Default date range when no authorize_date is provided
                $query->where('q.paid_at', '<=', DB::raw('DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 24 HOUR'))
                    ->where('q.paid_at', '>', DB::raw('DATE_ADD(NOW(), INTERVAL 4 HOUR) - INTERVAL 30 DAY'));
            })
            ->groupBy('t.name');

        $puaAuthTeamUpdate = $puaAuthTeamUpdate->get();

        return [$puaAuthUpdate, $puaAuthTeamUpdate];
    }

    public function exportPUAUpdates($requestParams = [])
    {
        LoggerService::info('CarQuoteService::exportPUAUpdates - Method called for Car PUA export');

        if (! empty($requestParams)) {
            $request = new Request($requestParams);
        } else {
            $request = request();
        }

        LoggerService::info('exportPUAUpdates', ['request_params' => $request->all()]);

        // Use selected date if provided, otherwise default to yesterday
        if ($request->filled('captured_date')) {
            $selectedDate = Carbon::parse($request->input('captured_date'))->subDay();
            $startDate = $selectedDate->startOfDay();
            $endDate = $selectedDate->copy()->endOfDay();
        } else {
            $startDate = Carbon::now()->subDay()->startOfDay();
            $endDate = Carbon::now()->subDay()->endOfDay();
        }

        $puaUpdatesQuery = DB::table('car_quote_plan_details as cqp')
            ->join('car_quote_request as cqr', 'cqp.quote_uuid', '=', 'cqr.uuid')
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.paymentable_id', '=', 'cqr.id')
                    ->where('p.paymentable_type', '=', 'App\\Models\\CarQuote');
            })
            ->join('car_make as cmk', 'cqr.car_make_id', '=', 'cmk.id')
            ->join('car_model as cmd', 'cqr.car_model_id', '=', 'cmd.id')
            ->join('nationality as n', 'cqr.nationality_id', '=', 'n.id')
            ->join('payment_status as ps', 'cqr.payment_status_id', '=', 'ps.id')
            ->join('quote_status as qs', 'cqr.quote_status_id', '=', 'qs.id')
            ->join('vehicle_type as vt', 'cqr.vehicle_type_id', '=', 'vt.id')
            ->leftJoin('car_plan as cp', 'cqr.plan_id', '=', 'cp.id')
            ->leftJoin('insurance_provider as ip', 'cp.provider_id', '=', 'ip.id')
            ->whereNotNull('cqp.pua_premium')
            ->whereNot(function ($q) {
                $q->where('cqp.is_renewal', true)
                    ->whereNotNull('cqp.pua_type');
            })
            ->whereBetween('cqr.payment_status_date', [$startDate, $endDate])
            ->whereIn('cqr.payment_status_id', [PaymentStatusEnum::CREDIT_APPROVED, PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PAID, PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::PARTIALLY_PAID])
            ->whereColumn('cqp.plan_id', 'cqr.plan_id')
            ->when($request->filled('captured_date'), function ($query) use ($request, $startDate, $endDate) {
                $capturedDate = $request->input('captured_date');
                if ($capturedDate) {
                    $query->whereBetween('p.captured_at', [
                        $startDate->startOfDay()->toDateTimeString(),
                        $endDate->endOfDay()->toDateTimeString(),
                    ]);
                }
            });

        return $puaUpdatesQuery;
    }

    public function pauseAndResumeFollowUpCounters($data)
    {

        $quote = CarQuote::where('uuid', $data['quote_uuid'])->first();

        if (! $quote || ! $quote->carQuoteRequestDetail) {
            return response()->json(['success' => false, 'message' => 'Quote or related details not found'], 200);
        }

        $field = $data['action'] === 'pause' ? 'followup_pause_count' : 'followup_resume_count';
        $quote->carQuoteRequestDetail->updateOrCreate(
            ['car_quote_request_id' => $quote->id],
            [
                $field => ($quote->carQuoteRequestDetail->$field ?? 0) + 1,
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Check if a user is a PCP advisor
     *
     * @param  int  $user_id  The ID of the user to check
     * @return bool True if the user is a PCP advisor, false otherwise
     */
    public function isPCPAdvisor($user_id)
    {
        try {
            // Cache the PCP team ID for 24 hours since it rarely changes
            $pcpTeamId = getTeamId(TeamNameEnum::PCP);

            // If $pcpTeamId is null or empty, the function will return false
            return ! empty($pcpTeamId) && UserTeams::where('user_id', $user_id)->where('team_id', $pcpTeamId)->exists();

        } catch (\Exception $e) {
            LoggerService::info(self::class.' - Error checking PCP advisor status: '.$e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
    public function exportCompanyCarPdf($quoteType, $data, $quotePlans = null, $imageData = [])
    {
        $planIds = $data['plan_ids'];
        $addons = (isset($data['addons'])) ? $data['addons'] : null;

        if ($quotePlans == null) {
            $quotePlans = $this->getQuotePlans($data['quote_uuid']);
        }

        if (! isset($quotePlans->quotes->plans)) {
            return ['error' => 'Quote plans not available'];
        }

        $quote = $this->getQuoteObjectBy($quoteType, $data['quote_uuid'], 'uuid');

        $quote->load(['carMake', 'carModel', 'advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no', 'profile_photo_path');
        }, 'customer', 'vehicleType']);

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150, 'isRemoteEnabled' => true])
            ->loadView('pdf.car_comparision.company_car_pdf', compact('quotePlans', 'planIds', 'quote', 'addons', 'imageData'));

        // generate pdf with file name e.g. InsuranceMarket.ae™ Motor Insurance Comparison for Rahul.pdf
        $pdfName = 'InsuranceMarket.ae™ Motor Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }

    public function getPreviousQuote($id)
    {
        return CarQuote::where('id', $id)->select('id', 'uuid', 'code')->first();
    }

}
