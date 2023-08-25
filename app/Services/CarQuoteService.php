<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Jobs\IntroEmailJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\Payment;
use App\Models\QuoteBatches;
use App\Models\QuoteViewCount;
use App\Models\Tier;
use App\Models\User;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PDF;

class CarQuoteService extends BaseService
{
    protected $query;
    protected $httpService;
    protected $childUserIds = [];
    protected $leadAllocationService;
    protected $sendEmailCustomerService;
    protected $applicationStorageService;
    use GenericQueriesAllLobs;
    use TeamHierarchyTrait;

    public function __construct(HttpRequestService $httpService, LeadAllocationService $leadAllocationService, SendEmailCustomerService $sendEmailCustomerService, ApplicationStorageService $applicationStorageService)
    {
        $this->leadAllocationService = $leadAllocationService;
        $this->httpService = $httpService;
        $this->applicationStorageService = $applicationStorageService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->query = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.uuid',
                'cqr.id',
                'cqr.first_name',
                'cqr.last_name',
                'cqr.email',
                'cqr.mobile_no',
                DB::raw('DATE_FORMAT(cqr.dob, "%d-%m-%Y") as dob'),
                'cqr.car_value',
                'cqr.additional_notes',
                'cqr.nationality_id',
                'cqr.year_of_manufacture',
                'cqr.code',
                'cqr.is_ecommerce',
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
                DB::raw('DATE_FORMAT(cqr.renewal_expiry_date, "%d-%m-%Y") as renewal_expiry_date'),
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
                DB::raw('DATE_FORMAT(cqrd.next_followup_date, "%d-%m-%Y %H:%i:%s") as next_followup_date'),
                'cqrd.transapp_code',
                'cqrd.notes',
                'cqrd.lost_approval_status',
                'cqrd.lost_approval_reason',
                'vt.text as vehicle_type_id_text',
                'cqr.currently_insured_with',
                'cqr.currently_insured_with as currently_insured_with_text',
                'ls.text as lost_reason',
                'cqr.previous_quote_policy_number',
                DB::raw('DATE_FORMAT(cqr.previous_policy_expiry_date, "%d-%m-%Y") as previous_policy_expiry_date'),
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
                'ulhfs.TEXT as back_home_license_held_for_id_text',
                DB::raw('DATE_FORMAT(cqr.policy_start_date, "%d-%m-%Y") as policy_start_date'),
                DB::raw('DATE_FORMAT(cqr.policy_issuance_date, "%d-%m-%Y") as policy_issuance_date'),
                'cqr.customer_id',
                'cqr.parent_duplicate_quote_id',
                'cqr.renewal_import_code',
                'cqr.quote_link',
                DB::raw('DATE_FORMAT(cqrd.advisor_assigned_date, "%d-%m-%Y %H:%i:%s") as advisor_assigned_date'),
                DB::raw("DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(),dob)), '%Y') + 0 AS customer_age"),
                'cqr.tier_id',
                't.name as tier_id_text',
                'qvc.visit_count as visit_count',
                't.cost_per_lead as cost_per_lead',
                'cqr.quote_batch_id',
                'qb.name as quote_batch_id_text',
                'cqr.car_value_tier'
            )
            ->leftJoin('nationality as n', 'n.id', '=', 'cqr.nationality_id')
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'cqrd.lost_reason_id')
            ->leftJoin('car_make as cmake', 'cmake.id', '=', 'cqr.car_make_id')
            ->leftJoin('uae_license_held_for as ulhf', 'ulhf.id', '=', 'cqr.uae_license_held_for_id')
            ->leftJoin('uae_license_held_for as ulhfs', 'ulhfs.id', '=', 'cqr.back_home_license_held_for_id')
            ->leftJoin('car_model as cmodel', 'cmodel.id', '=', 'cqr.car_model_id')
            ->leftJoin('emirates as e', 'e.id', '=', 'cqr.emirate_of_registration_id')
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
            ->leftJoin('claim_history as ch', 'ch.id', '=', 'cqr.claim_history_id')
            ->leftJoin('users as u', 'u.id', '=', 'cqr.advisor_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('vehicle_type as vt', 'vt.id', '=', 'cqr.vehicle_type_id')
            ->leftJoin('car_model_detail as cmd', 'cmd.id', '=', 'cqr.car_model_detail_id')
            ->leftJoin('tiers as t', 't.id', '=', 'cqr.tier_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'cqr.quote_batch_id')
            ->leftJoin('quote_view_count as qvc', function ($join) {
                $join->on('qvc.quote_id', 'cqr.id');
                $join->where('qvc.quote_type_id', QuoteTypeId::Car);
                $join->on('qvc.user_id', 'cqr.advisor_id');
            });
    }

    public function saveCarQuote(Request $request)
    {
        $dataArr = [
            'firstName' => $request->first_name,
            'lastName' => $request->last_name,
            'email' => $request->email,
            'address' => $request->address,
            'mobileNo' => $request->mobile_no,
            'dob' => $request->dob,
            'nationalityId' => $request->nationality_id,
            'uaeLicenseHeldForId' => $request->uae_license_held_for_id,
            'backHomeLicenseHeldForId' => $request->back_home_license_held_for_id,
            'yearOfManufacture' => $request->year_of_manufacture,
            'emirateOfRegistrationId' => $request->emirate_of_registration_id,
            'carTypeInsuranceId' => $request->car_type_insurance_id,
            'claimHistoryId' => $request->claim_history_id,
            'hasNcdSupportingDocuments' => $request->has_ncd_supporting_documents == GenericRequestEnum::Yes ? true : false,
            'additionalNotes' => $request->additional_notes,
            'carValue' => $request->car_value_tier,
            'carValueTier' => $request->car_value_tier,
            'seatCapacity' => $request->seat_capacity,
            'cylinder' => $request->cylinder,
            'vehicleTypeId' => $request->vehicle_type_id,
            'trim' => $request->trim,
            'premium' => $request->premium,
            'carMakeId' => CarMake::where('code', $request->car_make_id)->first() ? CarMake::where('code', $request->car_make_id)->first()->id : null, // ID
            'carModelId' => $request->car_model_id, // ID
            'currentlyInsuredWith' => $request->currently_insured_with,
            'source' => config('constants.SOURCE_NAME'),
            'referenceUrl' => config('constants.APP_URL'),
        ];

        if (! Auth::user()->hasRole('ADMIN')) {
            $dataArr['advisorId'] = Auth::user()->id;
        }
        info('Create triggered from IMCRM for Car Quote request with email : '.$request->email.' and sending request to CAPI');

        return CapiRequestService::sendCAPIRequest('/api/v1-save-car-quote', $dataArr);
    }

    public function updateCarQuote(Request $request, $id)
    {
        $carQuote = CarQuote::where('uuid', $id)->first();

        if (Auth::user()->hasRole(RolesEnum::CarManager)) {
            $carQuote->renewal_batch = $request->renewal_batch;
            $carQuote->updated_by = auth()->user()->email;

            $carQuote->save();

            if (isset($request->return_to_view)) {
                return redirect('quote/car/'.$carQuote->id)->with('success', 'Car Quote has been updated');
            }
        } else {
            $oldCarValue = $carQuote->car_value;
            info('Update triggered from IMCRM for Car Quote request with uuid : '.$carQuote->code);

            if ($request->first_name) {
                $carQuote->first_name = $request->first_name;
            }
            if ($request->last_name) {
                $carQuote->last_name = $request->last_name;
            }
            if ($request->dob) {
                $carQuote->dob = $request->dob;
            }
            if ($request->nationality_id) {
                $carQuote->nationality_id = $request->nationality_id;
            }
            if ($request->uae_license_held_for_id) {
                $carQuote->uae_license_held_for_id = $request->uae_license_held_for_id;
            }
            if ($request->back_home_license_held_for_id) {
                $carQuote->back_home_license_held_for_id = $request->back_home_license_held_for_id;
            }
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
            if ($request->has_ncd_supporting_documents) {
                $carQuote->has_ncd_supporting_documents = $request->has_ncd_supporting_documents == GenericRequestEnum::Yes ? true : false;
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
            $carQuote->quote_updated_at = Carbon::now();
            $carQuote->is_quote_locked = true;
            if ($request->trim) {
                $carQuote->car_model_detail_id = $request->trim;
            }
            if ($request->policy_start_date) {
                $carQuote->policy_start_date = $request->policy_start_date;
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
                info('Car value at enquiry is about to change from : '.$carQuote->car_value_tier.' to : '.$request->car_value_tier.' for lead : '.$carQuote->code);

                $carQuote->car_value_tier = $request->car_value_tier;

                $originalValue = $carQuote->car_value; // taking backup of original car_value

                $carQuote->car_value = $request->car_value_tier; // adding value tier because tier function uses car_value

                $selectedTier = $this->leadAllocationService->getTierForValue($carQuote);

                $carQuote->car_value = $originalValue; // adding back the original value since tier is now selected.

                info('After car value tier update the new selected tier is : '.$selectedTier->name.' for lead : '.$carQuote->code);

                $carQuote->tier_id = $selectedTier->id;
                $carQuote->cost_per_lead = $selectedTier->cost_per_lead;

                info('Car tier and cost per lead updated after value change for lead : '.$carQuote->code);
            }

            $carQuote->updated_by = auth()->user()->email;
            $deleteValuationResponse = $this->deleteValuationAPI($oldCarValue, $request->car_value, $carQuote->uuid);

            if ($deleteValuationResponse) {
                $carQuote->save();

                if (isset($request->return_to_view)) {
                    return redirect('quote/car/'.$carQuote->id)->with('success', 'Car Quote has been updated');
                }
            } else {
                return false;
            }
        }
    }

    public function getEntity($id)
    {
        return $this->query->where('cqr.uuid', $id)->first();
    }

    public function updateChildRecord($id)
    {
        $childRecord = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();

        if (! $childRecord) {
            $childRecord = $this->createDetailEntity($id);
        }
        $oldAdvisorAssignedDate = $childRecord->advisor_assigned_date;
        info('before update - Old advisor assigned date is : '.$oldAdvisorAssignedDate);
        $childRecord->advisor_assigned_by_id = Auth::user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();

        return $oldAdvisorAssignedDate;
    }

    public function updatedAccessAgainstPaymentStatus($paymentEntityModel, $record)
    {
        $carPayment = [];
        if ($paymentEntityModel->payments) {
            $carPayment = $paymentEntityModel->payments()->where('code', '=', $record->code)->first();
        }
        $quoteStatusArray = [QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::TransactionApproved];

        $access['carAdvisorCanEdit'] = false;
        $access['carManagerCanEdit'] = false;
        $access['carAdvisorCanEditPaymentCancelledRefund'] = false;
        $access['carAdvisorCanEditInsurer'] = false;
        $access['carManagerCanEditInsurer'] = false;

        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            if (! empty($record->payment_status_id) && $record->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                $access['carAdvisorCanEditInsurer'] = true;
            }
        }

        if (auth()->user()->hasRole(RolesEnum::CarManager)) {
            if (! empty($record->payment_status_id) && $record->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                $access['carManagerCanEditInsurer'] = true;
            }
        }
        // Car Advisor & Manager with payment status captured/Partially captured
        if (! empty($carPayment->captured_at)) {
            $paymentCapturedAt = $carPayment->captured_at;
            $today = Carbon::today();

            $dateLimitForAdvisor = Carbon::parse($paymentCapturedAt)->addDays(6);
            $dateLimitForManager = Carbon::parse($dateLimitForAdvisor)->addDays(6);

            if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
                if (! empty($record->payment_status_id) && in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) && $today->lte($dateLimitForAdvisor) && ! in_array($record->quote_status_id, $quoteStatusArray)) {
                    $access['carAdvisorCanEdit'] = true;
                    $access['carAdvisorCanEditInsurer'] = true;
                }
            }

            if (auth()->user()->hasRole(RolesEnum::CarManager)) {
                if (! empty($record->payment_status_id) && in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) && $today->gt($dateLimitForAdvisor) && $today->lte($dateLimitForManager) && ! in_array($record->quote_status_id, $quoteStatusArray)) {
                    $access['carManagerCanEdit'] = true;
                    $access['carManagerCanEditInsurer'] = true;
                }
            }
        }
        // Car Advisor
        if (auth()->user()->hasRole(RolesEnum::CarAdvisor)) {
            if (! empty($record->payment_status_id) && in_array($record->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PENDING, PaymentStatusEnum::FAILED, PaymentStatusEnum::DECLINED, PaymentStatusEnum::DRAFT, PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED])) {
                $access['carAdvisorCanEdit'] = true;
            }
            if (! empty($record->payment_status_id) && in_array($record->payment_status_id, [PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED])) {
                $access['carAdvisorCanEditPaymentCancelledRefund'] = true;
            }
        }
        // Car Manager
        if (auth()->user()->hasRole(RolesEnum::CarManager)) {
            if (! empty($record->payment_status_id) && in_array($record->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PENDING, PaymentStatusEnum::FAILED, PaymentStatusEnum::DECLINED, PaymentStatusEnum::DRAFT, PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED])) {
                $access['carManagerCanEdit'] = true;
            }
        }

        if (! empty($record->payment_status_id)) {
            if (in_array($record->payment_status_id, [PaymentStatusEnum::PARTIAL_CAPTURED, PaymentStatusEnum::CAPTURED]) && in_array($record->quote_status_id, $quoteStatusArray)) {
                $access['carAdvisorCanEdit'] = false;
                $access['carManagerCanEdit'] = false;
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
        $entity = CarQuoteRequestDetail::where('car_quote_request_id', $id)->first();
        if (! $entity) {
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

    public function getEntityPlain($id)
    {
        return CarQuote::where('id', $id)->first();
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
            'visit_count' => 'readonly|none',
            'next_followup_date' => 'input|date|title|range',
            'updated_at' => 'input|date|title',
            'updated_by' => 'readonly|none',
            'additional_notes' => 'textarea|',
            'advisor_id' => 'select|title||multiple|ss:17',
            'policy_number' => 'input|text|title',
            'renewal_expiry_date' => 'input|date|title|range|ss:15',
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
            case 'renewal_expiry_date':
                $title = 'Renewal Expiry Date';
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
            default:
                break;
        }

        return $title;
    }

    private function parseDate($date, $isStartOfDay)
    {
        if ($date != '') {
            if ($isStartOfDay) {
                return Carbon::parse($date)->startOfDay()->toDateTimeString();
            } else {
                return Carbon::parse($date)->endOfDay()->toDateTimeString();
            }
        }
    }

    public function walkTree($userId)
    {
        $carTeam = $this->getProductByName(quoteTypeCode::Car);
        array_push($this->childUserIds, $userId);
        if (auth()->user()->hasAnyRole([RolesEnum::CarManager, RolesEnum::LeadPool])) {
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
                $carUserIds = $this->getUsersByTeamId($carTeam->id)->pluck('id');
                $nextChild = DB::table('user_manager')->where('manager_id', $teamMateId)->whereIn('user_id', $carUserIds)->pluck('user_id');
                if (count($nextChild) > 0) {
                    $this->walkTree($teamMateId);
                }
                array_push($this->childUserIds, $teamMateId);
            }
        }
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;

        if ($request->ajax()) {
            $this->addLeadViewEligibilityCheck();

            if (
                empty($request->email) && empty($request->code) && empty($request->first_name) &&
                empty($request->last_name) && empty($request->quote_status_id) && empty($request->mobile_no)
            ) {
                $this->query->whereNotIn('cqr.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]);
            }

            if (isset($request->advisor_assigned_date) && $request->advisor_assigned_date != '') {
                $dateFrom = $this->parseDate($request['advisor_assigned_date'], true);
                $dateTo = $this->parseDate($request['advisor_assigned_date_end'], false);
                $this->query->whereBetween(DB::raw('DATE(cqrd.advisor_assigned_date)'), [$dateFrom, $dateTo]);
            }
            if (isset($request->renewal_expiry_date) && $request->renewal_expiry_date != '') {
                $dateFrom = $this->parseDate($request['renewal_expiry_date'], true);
                $dateTo = $this->parseDate($request['renewal_expiry_date_end'], false);
                $this->query->whereBetween(DB::raw('DATE(cqr.previous_policy_expiry_date)'), [$dateFrom, $dateTo]);
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = $this->parseDate($request['next_followup_date'], true);
                $dateTo = $this->parseDate($request['next_followup_date_end'], false);
                $this->query->whereBetween(DB::raw('DATE(cqrd.next_followup_date)'), [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != '') {
                $dateFrom = $this->parseDate($request['created_at'], true);
                $dateTo = $this->parseDate($request['created_at_end'], false);
                $this->query->whereBetween(DB::raw('cqr.created_at'), [$dateFrom, $dateTo]);
            }

            foreach ($searchProperties as $item) {
                if (! empty($request[$item]) && $item != 'created_at' && $item != 'renewal_expiry_date' && $item != 'advisor_assigned_date') {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } elseif ($item == 'advisor_id' && is_array($request[$item]) && ! empty($request[$item])) {
                        if ($request[$item][0] == 'null') {
                            $this->query->whereNull('cqr.advisor_id');
                        } else {
                            $this->query->whereIn('cqr.advisor_id', $request[$item]);
                        }
                    } elseif ($item == 'quote_status_id' && is_array($request[$item]) && ! empty($request[$item])) {
                        $this->query->whereIn('cqr.quote_status_id', $request[$item]);
                    } elseif ($item == 'tier_id' && is_array($request[$item]) && ! empty($request[$item])) {
                        $this->query->whereIn('cqr.tier_id', $request[$item]);
                    } elseif ($item == 'quote_batch_id' && is_array($request[$item]) && ! empty($request[$item])) {
                        $this->query->whereIn('qb.id', $request[$item]);
                    } else {
                        $searchedValue = preg_match("/\b".'Yes'."\b/i", $request[$item]) || preg_match("/\b".'No'."\b/i", $request[$item]) ? ($request[$item] == 'Yes' ? 1 : 0) : $request[$item];
                        if ($item == 'policy_number') {
                            $this->query->where('cqr.previous_quote_policy_number', $searchedValue);
                        } elseif ($item == 'show_renewal_upload_leads') {
                            if ($request[$item] == 'Yes') {
                                $this->query->where('cqr.source', LeadSourceEnum::RENEWAL_UPLOAD);
                            } else {
                                $this->query->where('cqr.source', '!=', LeadSourceEnum::RENEWAL_UPLOAD);
                            }
                        } else {
                            $this->query->where($this->getQuerySuffix($item).'.'.$item, $searchedValue);
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
            return $this->query->orderBy('cqr.created_at', 'DESC');
        }
    }

    private function addLeadViewEligibilityCheck()
    {
        if (Auth::user()->hasRole(RolesEnum::CarManager) || Auth::user()->hasRole(RolesEnum::CarDeputyManager)) {
            $this->walkTree(Auth::user()->id);
            $this->query->whereIn('cqr.advisor_id', $this->childUserIds);
        } elseif (Auth::user()->hasRole(RolesEnum::LeadPool)) {
            $this->walkTree(Auth::user()->id);
            $this->query->where(function ($query) {
                return $query->whereIn('cqr.advisor_id', $this->childUserIds)->OrWhereNull('cqr.advisor_id');
            });
        } elseif (Auth::user()->hasRole(RolesEnum::CarAdvisor)) {
            $this->query->where('cqr.advisor_id', Auth::user()->id);
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
            case 'advisor_assigned_date':
                return 'cqrd.advisor_assigned_date';
                break;
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
            default:
                return 'cqr';
                break;
        }
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
            'create' => 'is_modified,is_gcc_standard,year_of_first_registration,parent_duplicate_quote_id,id,advisor_id,paid_at,lost_reason,payment_status_id,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,renewal_expiry_date,renewal_batch,premium,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code,customer_age,tier_id,visit_count,cost_per_lead,quote_batch_id,policy_start_date,advisor_assigned_date,car_value,show_renewal_upload_leads',
            'list' => 'policy_start_date,transapp_code,seat_capacity,cylinder,has_ncd_supporting_documents,back_home_license_held_for_id,parent_duplicate_quote_id,trim,email,mobile_no,paid_at,plan_id,car_plan_provider_id,payment_gateway,promo_code,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,emirate_of_registration_id,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code,customer_age,renewal_batch,show_renewal_upload_leads',
            'update' => 'is_modified,is_gcc_standard,year_of_first_registration,parent_duplicate_quote_id,id,advisor_id,paid_at,renewal_expiry_date,payment_status_id,lost_reason,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,source,transapp_code,previous_quote_policy_premium,quote_status_id,car_model_detail_id,renewal_import_code,customer_age,tier_id,visit_count,cost_per_lead,quote_batch_id,policy_start_date,advisor_assigned_date,show_renewal_upload_leads',
            'show' => 'is_modified,is_gcc_standard,trim,previous_quote_id,plan_id,premium,payment_status_id,paid_at,car_plan_provider_id,payment_gateway,quote_status_id,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,renewal_import_code,tier_id,visit_count,id,renewal_batch,policy_start_date,is_ecommerce,quote_link,transapp_code,order_reference,payment_reference,policy_number,renewal_expiry_date,lost_reason,show_renewal_upload_leads',
        ];
    }

    public function fillModelSearchProperties()
    {
        $searchProperties = ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'currently_insured_with', 'renewal_expiry_date', 'is_ecommerce', 'payment_status_id', 'renewal_batch', 'previous_quote_policy_number', 'car_type_insurance_id', 'vehicle_type_id', 'advisor_assigned_date', 'tier_id', 'quote_batch_id', 'advisor_id', 'show_renewal_upload_leads'];

        return $searchProperties;
    }

    public function fillRenewalProperties($model)
    {
        $model->renewalSearchProperties = ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'created_at', 'vehicle_type_id', 'renewal_expiry_date', 'is_ecommerce', 'payment_status_id', 'renewal_batch', 'car_type_insurance_id', 'currently_insured_with', 'previous_quote_policy_number'];
        $model->renewalSkipProperties = [
            'create' => 'policy_start_date,parent_duplicate_quote_id,id,advisor_id,paid_at,renewal_expiry_date,renewal_batch,lost_reason,payment_status_id,plan_id,premium,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,quote_status_id,device,policy_number,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,premium,source,transapp_code,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code',
            'list' => 'policy_start_date,parent_duplicate_quote_id,trim,additional_notes,email,mobile_no,paid_at,plan_id,car_plan_provider_id,payment_gateway,promo_code,source,seat_capacity,cylinder,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,nationality_id,dob,year_of_manufacture,uae_license_held_for_id,car_value,emirate_of_registration_id,claim_history_id,transapp_code,is_ecommerce,payment_status_id,policy_number,renewal_expiry_date,premium,car_model_detail_id,renewal_import_code',
            'update' => 'parent_duplicate_quote_id,id,advisor_id,paid_at,payment_status_id,lost_reason,plan_id,car_plan_provider_id,code,is_ecommerce,payment_gateway,created_at,next_followup_date,updated_at,promo_code,device,previous_quote_id,order_reference,payment_reference,calculated_value,created_by,updated_by,renewal_expiry_date,source,transapp_code,quote_status_id,renewal_batch,policy_number,previous_quote_policy_number,previous_policy_expiry_date,previous_quote_policy_premium,car_model_detail_id,renewal_import_code',
            'show' => 'trim,device,previous_quote_id,plan_id,premium,payment_status_id,paid_at,car_plan_provider_id,payment_gateway,cylinder,seat_capacity,quote_status_id,vehicle_type_id',
        ];
    }

    public function getQuotePlans($id, $isRenewalSort = false, $getLatestRating = false, $isDisabledEnabled = false)
    {
        $quoteUuId = CarQuote::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-car-quote-plans';
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
        ];

        if ($isDisabledEnabled) {
            $plansDataArr['filters'][] = [
                'field' => 'isDisabled',
                'value' => false,
            ];
        }

        $client = new \GuzzleHttp\Client();

        try {
            $kenRequest = $client->post(
                $plansApiEndPoint,
                [
                    'headers' => [
                        'Content-Type' => 'application/json', 'Accept' => 'application/json',
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
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

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
     * @param $data
     * @return false
     */
    public function renewalCreatePlan($planData)
    {
        $apiCreds = [
            'apiEndPoint' => config('constants.KEN_API_ENDPOINT').'/save-manual-car-quote-plan',
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
                $this->lockCarQuote($request->car_quote_uuid);
            }
        }

        return $response;
    }

    /**
     * @return bool|string
     * paid_at = authorized date
     */
    public function isPlanModifyAllowed($data)
    {
        $logPrefix = 'fn: isPlanModifyAllowed ';
        $quote = CarQuote::where('uuid', $data['car_quote_uuid'])->with('paymentStatus')->first();

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            $carPayment = Payment::where('code', '=', $quote->code)->first();
            if (! empty($carPayment->captured_at)) {
                $paymentCapturedAt = $carPayment->captured_at;
                $today = Carbon::today();

                $dateLimitForAdvisor = Carbon::parse($paymentCapturedAt)->addDays(6);
                $dateLimitForManager = Carbon::parse($dateLimitForAdvisor)->addDays(6);

                if (Auth::user()->hasRole(RolesEnum::CarAdvisor) && $today->lte($dateLimitForAdvisor)) {
                    info($logPrefix.' plan modify allowed to advisor for uuid '.$quote->uuid.' and captured days diff is '.$paymentCapturedAt);

                    return true;
                } elseif (Auth::user()->hasRole(RolesEnum::CarManager) && $today->gt($dateLimitForAdvisor) && $today->lte($dateLimitForManager)) {
                    info($logPrefix.' plan modify allowed to car manager for uuid '.$quote->uuid.' and captured days diff is '.$paymentCapturedAt);

                    return true;
                }
            }
        }

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED]) && Auth::user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager, RolesEnum::CarManager])) {
            info($logPrefix.' plan modify allowed to advisor for uuid '.$quote->uuid);

            return true;
        }

        if (
            $quote->payment_status_id == '' || $quote->payment_status_id == null || (in_array($quote->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PENDING, PaymentStatusEnum::FAILED, PaymentStatusEnum::DECLINED, PaymentStatusEnum::DRAFT])
        && Auth::user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarDeputyManager, RolesEnum::CarManager]))
        ) {
            info($logPrefix.' plan modify allowed for uuid '.$quote->uuid);

            return true;
        }

        info($logPrefix.' plan modification is not allowed for uuid '.$quote->uuid);

        return 'Plan Modification is not allowed';
    }

    public function getDuplicateEntityByCode($code)
    {
        return CarQuote::where('parent_duplicate_quote_id', $code)->first();
    }

    public function getPlans($id, $isRenewalSort = false, $isDisabledEnabled = false)
    {
        $quotePlans = $this->getQuotePlans($id, $isRenewalSort, false, $isDisabledEnabled);

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

        foreach ($this->getLeadIdsToProcessFromRequest($request) as $leadId) {
            $lead = $this->getEntityPlain($leadId);

            $isReassignment = $lead->advisor_id != null ? true : false; // checking if the advisor is already assigned or not for reassignment email template

            $previousAdvisorId = $lead->advisor_id; // saving previous advisor before updating the new to update the counts

            $lead->advisor_id = $userId;

            $lead->assignment_type = AssignmentTypeEnum::MANUAL_ASSIGNED;

            $quoteBatch = QuoteBatches::latest()->first();

            info('About to assign quote batch with id : '.$quoteBatch->id.' and with name : '.$quoteBatch->name.' to quote : '.$lead->uuid);

            $lead->quote_batch_id = $quoteBatch->id;

            $this->updateTierAndCost($lead); // will assign/update tier and update cost per lead from tier

            info('Manual assignment done for lead : '.$lead->uuid);

            $oldAdvisorAssignedDate = $this->updateChildRecord($lead->id); // will update the car quote request detail entity about assignment

            info('after update Old advisor assigned date is : '.$oldAdvisorAssignedDate);

            info('Assigned Date and id are update in details table for lead : '.$lead->uuid);

            $this->addManualAllocationCountAndUpdate($userId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate); // update new and previous (if applicable) advisor counts in lead allocation table

            $this->updateExistingQuoteViewCount($userId, $lead->id); // update existing record of quote view count if exists and reset count to zero

            $lead->auto_assigned = false;

            $lead->save();

            if (isset($request->assignment_type) && $request->assignment_type == GenericRequestEnum::ASSIGN_WITH_EMAIL) {
                info('Inside sending email for manual assignment');

                $currentAdvisor = User::where('id', $userId)->first();

                $documentUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL)->first()->value;

                $emailData = (object) [
                    'customerEmail' => $lead->email,
                    'documentUrl' => [$documentUrl], // this will be replace with a generic URL once document upload section is done
                    'clientFullName' => $lead->first_name.' '.$lead->last_name,
                    'advisorName' => $currentAdvisor->name,
                    'landLine' => $currentAdvisor->landline_no,
                    'mobilePhone' => $currentAdvisor->mobile_no,
                    'advisorEmail' => $currentAdvisor->email,
                    'carQuoteId' => $lead->code,
                    'quoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid,
                ];

                $emailTemplateIdReassign = (int) $this->applicationStorageService->getValueByKey('LMS_REASSIGN_EMAIL_TEMPLATE_ID');
                $emailTemplateIIntro = (int) $this->applicationStorageService->getValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID');

                IntroEmailJob::dispatch(quoteTypeCode::Car, $isReassignment ? $emailTemplateIdReassign : $emailTemplateIIntro, $emailData, 'send-lms-reassignment-email');
            }
        }

        return [];
    }

    private function updateExistingQuoteViewCount($userId, $leadId)
    {
        $quoteViewCount = QuoteViewCount::where('quote_id', $leadId)->first();
        if ($quoteViewCount) {
            $quoteViewCount->user_id = $userId;
            $quoteViewCount->visit_count = 0;
            $quoteViewCount->save();
        }
    }

    public function updateTierAndCost($lead)
    {
        if ($lead->tier_id == null) {
            info('Manual assignment: tier is not assigned, evaluating tier now');
            $selectedTier = $this->leadAllocationService->getTierForValue($lead);

            if ($selectedTier) {
                info('Found tier : '.$selectedTier->name.', with id : '.$selectedTier->id.' against lead : '.$lead->code);
                $lead->tier_id = $selectedTier->id;
                info('since tier is now assigned, we will update the cost per lead from tier');
                $lead->cost_per_lead = $selectedTier->cost_per_lead;
            } else {
                info('Unable to find tier against lead : '.$lead->code);
            }
        } else {
            info('since tier is assigned, we will update the cost per lead from tier');
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
        info('Leads ids for manual assign: '.json_encode($leadIds));

        return $leadIds;
    }

    public function addManualAllocationCountAndUpdate($userId, $lead, $previousAdvisorId, $oldAdvisorAssignedDate)
    {
        info('lead current advisor_id is : '.json_encode($previousAdvisorId).' and lead created date is : '.$lead->created_at);
        $shouldUpdateAllocationRecord = $userId != $previousAdvisorId;
        $newAdvisorAllocationRecord = $this->leadAllocationService->getLeadAllocationRecordByUserId($userId);
        $previousAdvisorAllocationRecord = null;
        if ($previousAdvisorId != null) {
            $previousAdvisorAllocationRecord = $this->leadAllocationService->getLeadAllocationRecordByUserId($previousAdvisorId);
        }
        if ($shouldUpdateAllocationRecord) {
            info('new advisor ('.$userId.')  manual count before update is : '.$newAdvisorAllocationRecord->manual_assignment_count.' and auto assignment count is : '.$newAdvisorAllocationRecord->auto_assignment_count);
            $newAdvisorAllocationRecord->manual_assignment_count = $newAdvisorAllocationRecord->manual_assignment_count + 1;
            $newAdvisorAllocationRecord->allocation_count = $newAdvisorAllocationRecord->allocation_count + 1;
            $newAdvisorAllocationRecord->updated_at = now();
            $newAdvisorAllocationRecord->save();
        }
        info('new advisor after update is : '.json_encode($newAdvisorAllocationRecord));
        if ($previousAdvisorId != null && Carbon::parse($oldAdvisorAssignedDate)->startOfDay() == now()->startOfDay()) { // will remove manual count from previous advisor lead is from current day only
            if ($lead->auto_assigned || $lead->auto_assigned == null) {
                if ($previousAdvisorAllocationRecord != null && $previousAdvisorAllocationRecord->auto_assignment_count > 0) {
                    info('previous advisor ('.$userId.')  auto assignment count is : '.$previousAdvisorAllocationRecord->auto_assignment_count);
                    $previousAdvisorAllocationRecord->auto_assignment_count = $previousAdvisorAllocationRecord->auto_assignment_count - 1;
                }
            } else {
                if ($previousAdvisorAllocationRecord != null && $previousAdvisorAllocationRecord->manual_assignment_count > 0) {
                    info('previous advisor ('.$userId.')  manual count before update is : '.$previousAdvisorAllocationRecord->manual_assignment_count);
                    $previousAdvisorAllocationRecord->manual_assignment_count = $previousAdvisorAllocationRecord->manual_assignment_count - 1;
                }
            }
            if ($previousAdvisorAllocationRecord != null && $previousAdvisorAllocationRecord->allocation_count > 0) { // will reduce count for previous advisor if the count is greater than 0 to avoid going in -1
                info('previous advisor ('.$userId.')  allocation_count count before update is : '.$previousAdvisorAllocationRecord->allocation_count);
                $previousAdvisorAllocationRecord->allocation_count = $previousAdvisorAllocationRecord->allocation_count - 1;
                $previousAdvisorAllocationRecord->updated_at = now();
                $previousAdvisorAllocationRecord->save();
                info('previous advisor after update is : '.json_encode($previousAdvisorAllocationRecord));
            }
        }
        info('new advisor alloc. count :'.$newAdvisorAllocationRecord->allocation_count.', manual count :'.$newAdvisorAllocationRecord->manual_assignment_count.', auto count :'.$newAdvisorAllocationRecord->auto_assignment_count);
        if ($previousAdvisorAllocationRecord != null) {
            info('previous advisor alloc. count :'.$previousAdvisorAllocationRecord->allocation_count.', manual count :'.$previousAdvisorAllocationRecord->manual_assignment_count.', auto count :'.$previousAdvisorAllocationRecord->auto_assignment_count);
        }
        info('assignment count update for userId : '.$userId.', and lead code :  '.$lead->code);
    }

    public function getEntityPlainByUUID($uuid)
    {
        return CarQuote::where('uuid', $uuid)->first();
    }

    public function validateRequest($request)
    {
        $isLeadPool = Auth::user()->isLeadPool();
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
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved && ! ($isLeadPool)) {
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
            $data = explode(',', $request->planIds);
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

        $quote = $this->getQuoteObject($quoteType, $data['quote_uuid']);
        $quote->load(['carMake', 'carModel', 'advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no');
        }, 'customer']);

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.quote_plans', compact('quotePlans', 'planIds', 'quote', 'addons'));

        // generate pdf with file name e.g. InsuranceMarket.ae™ Motor Insurance Comparison for Rahul.pdf
        $pdfName = 'InsuranceMarket.ae™ Motor Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }

    public function addOrUpdateQuoteViewCount($record)
    {
        if ($record->advisor_id != null && $record->advisor_id == Auth::user()->id) {
            // Search for an existing record with the same quote_id and user_id
            $quoteViewCount = QuoteViewCount::where('quote_id', $record->id)
                ->where('user_id', Auth::user()->id)
                ->first();

            if ($quoteViewCount) {
                // If the record exists, increment its visit_count
                info('Quote view count record found for lead : '.$record->code);
                $quoteViewCount->increment('visit_count');
            } else {
                info('Quote view count record not found for lead : '.$record->code);
                // If the record does not exist, create a new one
                QuoteViewCount::create([
                    'quote_id' => $record->id,
                    'quote_type_id' => 1,
                    'user_id' => Auth::user()->id,
                    'visit_count' => 1,
                ]);
            }
        }
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
            Log::info('Delete Valuation API Error: '.$exception->getMessage());

            return false;
        }
    }
}
