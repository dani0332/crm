<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\ExportLogsTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\InsurerProviderEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentCaptureValidationEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Capi;
use App\Facades\Ken;
use App\Facades\Marshall;
use App\Http\Requests\SplitPaymentApproveRequest;
use App\Jobs\AutomationFailedJob;
use App\Models\Activities;
use App\Models\ActivitySchedule;
use App\Models\ApplicationStorage;
use App\Models\BrokerCommission;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\InsuranceProvider;
use App\Models\LifeQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusHistory;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use App\Models\QuoteBatches;
use App\Models\QuoteExportLog;
use App\Models\QuoteFlowDetails;
use App\Models\QuoteStatusLog;
use App\Models\QuoteType;
use App\Models\SendUpdateLog;
use App\Models\SendUpdateStatusLog;
use App\Models\Team;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use App\Repositories\PaymentRepository;
use App\Repositories\PersonalQuoteRepository;
use App\Services\Life\LifeQuoteService;
use App\Services\Logger\LoggerService;
use App\Services\Quotes\SavingsQuoteService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\HandlesDeadlockRetries;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CentralService extends BaseService
{
    use GenericQueriesAllLobs, HandlesDeadlockRetries, TeamHierarchyTrait;

    public function duplicateAllowedLobsList($quoteType, $leadCode)
    {
        $allowedLeadTypes = [
            quoteTypeCode::Home,
            quoteTypeCode::Health,
            quoteTypeCode::Life,
            quoteTypeCode::CORPLINE,
            quoteTypeCode::GroupMedical,
            quoteTypeCode::Travel,
            quoteTypeCode::Car,
            quoteTypeCode::Pet,
            quoteTypeCode::Cycle,
            quoteTypeCode::SAVINGS,
        ];

        if (strtolower($quoteType) == strtolower(quoteTypeCode::Business)) {
            $modelType = quoteTypeCode::CORPLINE;
        }
        $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
            return $item;
        });
        foreach ($allowedLeadTypes as $leadType) {
            $leadType = strtolower($leadType);

            if ($leadType == strtolower(quoteTypeCode::CORPLINE) || $leadType = strtolower(quoteTypeCode::GroupMedical)) {
                $leadType = quoteTypeCode::Business;
            }

            $repository = $this->getRepositoryObject(ucfirst($leadType));

            $duplicateRecord = $repository::where('code', $leadCode)->first();
            if ($duplicateRecord) {
                $allowedLeadTypes = array_filter($allowedLeadTypes, function ($item) {
                    return $item;
                });
            }
        }

        return $allowedLeadTypes;
    }

    public function saveDuplicateLeads($data)
    {
        $lobTeams = $data['lob_team'];
        $parentType = $data['parentType'];
        $entityId = $data['entityId'];

        if (strtolower($parentType) == strtolower(quoteTypeCode::CORPLINE) || strtolower($parentType) == strtolower(quoteTypeCode::GroupMedical)) {
            $parentType = quoteTypeCode::Business;
        }

        $parentRecord = null;

        if ($quoteType = QuoteTypes::tryFrom($parentType)) {
            $parentRecord = $quoteType->model()::find($entityId);
        }

        if (! $parentRecord) {
            $repository = $this->getRepositoryObject($parentType);
            if ($repository) {
                $parentRecord = $repository::where('id', $entityId)->first();
            }
        }

        if (! $parentRecord) {
            return false;
        }

        if (! empty($data['lob_team_sub_selection'])) {
            $parentRecord['enquiryType'] = $data['lob_team_sub_selection'];
        } else {
            $parentRecord['enquiryType'] = 'record_only';
        }

        if (! empty($lobTeams)) {
            $dataArr = [
                'firstName' => $parentRecord->first_name,
                'lastName' => $parentRecord->last_name,
                'email' => $parentRecord->email,
                'mobileNo' => $parentRecord->mobile_no,
                'referenceUrl' => config('constants.APP_URL'),
                'source' => config('constants.SOURCE_NAME'),
            ];

            $resp = [];
            foreach ($lobTeams as $lob) {
                if (strtolower($lob) == strtolower(quoteTypeCode::CORPLINE) || strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                    $dataArr['businessTypeOfInsuranceId'] = $parentRecord->business_type_of_insurance_id ?? '';
                    $dataArr['companyName'] = $parentRecord->company_name ?? '';
                    $dataArr['numberOfEmployees'] = $parentRecord->number_of_employees ?? '';
                    $dataArr['healthPlanTypeId'] = $parentRecord->health_plan_type_id ?? '';
                    if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                        $dataArr['businessTypeOfInsuranceId'] = QuoteTypeId::Business;
                    }
                    $lob = quoteTypeCode::Business;
                }

                if (in_array($lob, [
                    quoteTypeCode::SAVINGS,
                ])) {
                    $response = PersonalQuoteRepository::createDuplicate($dataArr, ucfirst($lob));
                } else {
                    $repository = $this->getRepositoryObject(ucfirst($lob));

                    if (! class_exists($repository)) {
                        return false;
                    }

                    $response = ((method_exists($repository, 'fetchCreateDuplicate') && ! checkPersonalQuotes(ucfirst($lob))) ? $repository::createDuplicate($dataArr) : PersonalQuoteRepository::createDuplicate($dataArr, ucfirst($lob)));
                }

                if (empty($response) || (isset($response->message) && str_contains($response->message, 'Error'))) {
                    $resp['errors'][] = 'Something went wrong while duplicating '.$lob.' quotes';
                } elseif (isset($response->quoteUID) && isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
                    $record = $repository::where('uuid', $response->quoteUID)->first();
                    if ($record) {
                        $update = [
                            'parent_duplicate_quote_id' => $parentRecord->code,
                            'advisor_id' => auth()->user()->id,
                            'assignment_type' => AssignmentTypeEnum::SELF_ASSIGNED,
                        ];
                        if (strtolower($lob) == strtolower(quoteTypeCode::Health)) {
                            $subTeam = null;
                            if (auth()->user()->subTeam) {
                                $subTeam = auth()->user()->subTeam->name;
                            }
                            $update['health_team_type'] = $subTeam;
                        }
                        $record->update($update);
                    }
                }
            }

            return $resp;
        }
    }

    public function assignLeadToAdvisor($request)
    {
        $leadsIds = $request->assigned_lead_id;
        $personalQuotes = [quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Pet, quoteTypeCode::Yacht, quoteTypeCode::Jetski, quoteTypeCode::SAVINGS, quoteTypeCode::Home];
        $quoteBatch = QuoteBatches::latest()->first();
        LoggerService::info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);

        if (str_starts_with($leadsIds, ',')) {
            $leadsIds = substr($leadsIds, 1);
        }

        $leadsIds = array_map('intval', explode(',', $leadsIds));
        $model = (in_array(ucfirst($request->modelType), $personalQuotes)) ?
            ['parent' => PersonalQuote::class, 'child' => PersonalQuoteDetail::class] :
            ['parent' => (ucfirst($request->modelType).'Quote'), 'child' => (ucfirst($request->modelType).'QuoteRequestDetail')];

        if (! class_exists($model['parent'])) {
            vAbort('Something went wrong');
        }

        return DB::transaction(function () use ($leadsIds, $model, $request, $personalQuotes, $quoteBatch) {
            foreach ($leadsIds as $leadId) {
                $getQuoteLead = $model['parent']::findOrfail($leadId);

                $oldAssignmentType = $getQuoteLead->assignment_type;
                $isReassignment = $getQuoteLead->advisor_id != null ? true : false;
                $previousAdvisorId = $getQuoteLead->advisor_id;

                $getQuoteLead->advisor_id = (int) $request->assigned_advisor_id;
                $getQuoteLead->assignment_type = $isReassignment ? AssignmentTypeEnum::MANUAL_REASSIGNED : AssignmentTypeEnum::MANUAL_ASSIGNED;
                LoggerService::info(self::class.' - assignLeadToAdvisor: Checking lead_assignment_trigger', extra: [
                    'current_value' => $getQuoteLead->lead_assignment_trigger ?? 'null',
                ]);
                if (empty($getQuoteLead->lead_assignment_trigger)) {
                    LoggerService::info(self::class.' - assignLeadToAdvisor: Setting lead_assignment_trigger to MANUAL_ALLOCATION');
                    $getQuoteLead->lead_assignment_trigger = LeadAssignmentTriggerEnum::MANUAL_ALLOCATION;
                }
                $getQuoteLead->quote_batch_id = $quoteBatch->id;
                $getQuoteLead->save();

                $parentFieldName = (in_array(ucfirst($request->modelType), $personalQuotes)) ?
                    'personal_quote_id' : strtolower($request->modelType).'_quote_request_id';

                $childRecord = $model['child']::where($parentFieldName, $getQuoteLead->id)->first();
                $oldAdvisorAssignedDate = $childRecord?->advisor_assigned_date ?? null;

                $model['child']::updateOrCreate(
                    [$parentFieldName => $getQuoteLead->id],
                    ['advisor_assigned_by_id' => auth()->user()->id, 'advisor_assigned_date' => Carbon::now()]
                );

                $quoteTypeId = $getQuoteLead?->quote_type_id ?? QuoteTypes::tryFrom(ucfirst(request('quoteType')))?->id();

                if ($quoteTypeId) {
                    $this->upsertManualAllocationCount($getQuoteLead->advisor_id, $getQuoteLead, $previousAdvisorId, $oldAdvisorAssignedDate, $oldAssignmentType, $quoteTypeId);

                    $this->addOrUpdateQuoteViewCount($getQuoteLead, $quoteTypeId, $getQuoteLead->advisor_id);
                }

                $getQuoteLead->auto_assigned = false;

                $getQuoteLead->save();
            }
        });
    }

    public function loadAvailablePlans($type, $id, $isRenewalSort = false, $isDisabledEnabled = false, $getLatestRating = false)
    {
        $type = ucfirst($type);
        switch ($type) {
            case quoteTypeCode::Car:
                return app(CarQuoteService::class)->getPlans($id);
            case quoteTypeCode::Travel:
                return app(TravelQuoteService::class)->sortedPlansList($id);
            case quoteTypeCode::Life:
                $listQuotePlans = [];
                $quotePlans = app(LifeQuoteService::class)->getQuotePlans($id);

                if (isset($quotePlans->message) && $quotePlans->message != '') {
                    $listQuotePlans = [];
                } else {
                    if (gettype($quotePlans) != 'string' && isset($quotePlans->quotes->plans)) {
                        $listQuotePlans[] = $quotePlans->quotes->plans;
                    }
                }

                return $listQuotePlans;
            case quoteTypeCode::Health:
                $listQuotePlans = [];

                $quotePlans = app(HealthQuoteService::class)->getQuotePlans($id);
                if (isset($quotePlans->message) && $quotePlans->message != '') {
                    $listQuotePlans = [];
                } else {
                    if (gettype($quotePlans) != 'string') {
                        $listQuotePlans[] = $quotePlans->quote->plans;

                        foreach ($listQuotePlans as $plans) {
                            foreach ($plans as $plan) {
                                if (isset($plan->planTypeId)) {
                                    $plan->plan_type = HealthPlanTypeEnum::typeName($plan->planTypeId)?->label();
                                } else {
                                    $plan->plan_type = 'N/A';
                                }
                            }
                        }
                    }
                }

                return $listQuotePlans;
            case quoteTypeCode::Bike:
                return $this->getPlans($type, $id, $isRenewalSort, $isDisabledEnabled);
            case quoteTypeCode::Home:
                return app(HomeQuoteService::class)->getQuotePlans($id, ['getLatestRating' => $getLatestRating]);
            case quoteTypeCode::SAVINGS:
                return app(SavingsQuoteService::class)->getAvailablePlans($id);
            default:
                return [];
        }
    }

    public function updateQuotePayment($quote, $priceWithVat, $insuranceProviderId)
    {
        LoggerService::info('fn: updateQuotePayment called');

        if ($quote->payments()->count() > 0) {
            LoggerService::info('fn: updateQuotePayment payment found to be updated for quote uuid: '.$quote->uuid);

            $payment = $quote->payments->first();

            $paymentData = ['total_price' => $priceWithVat];

            if ($insuranceProviderId) {
                $paymentData['insurance_provider_id'] = $insuranceProviderId;
            }

            if ($priceWithVat > $payment->total_price && in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])) {
                $paymentData['payment_status_id'] = PaymentStatusEnum::PARTIALLY_PAID;
            }

            if ($payment->frequency == PaymentFrequency::UPFRONT && $payment->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                if ($payment->premium_authorized > 0 && $priceWithVat <= $payment->premium_authorized) {
                    $paymentData['total_amount'] = $priceWithVat;
                    // update total amount of first split payment
                    $payment->paymentSplits()->first()->update(['payment_amount' => $priceWithVat]);
                }
            }

            $payment->update($paymentData);

            LoggerService::info('fn: updateQuotePayment payment updated for quote uuid: '.$quote->uuid);
        }
    }

    /**
     * @return true
     */
    public function savePlanDetails($quoteType, $code, $data)
    {
        $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
        $repository = getRepositoryObject($quoteType);
        $quote = $repository::where('code', $code)->firstOrFail();

        $priceVatApp = $data->price_vat_applicable ?? 0;
        $priceVatNotApp = $data->price_vat_not_applicable ?? 0;
        $vatAmount = ($priceVatApp / 100) * $vatPercentage;
        LoggerService::info("Quote {$code} - VAT values: priceVatApp: {$priceVatApp}, priceVatNotApp: {$priceVatNotApp}, vatAmount: {$vatAmount}");

        if ($quoteType == QuoteTypes::BUSINESS->value) {
            $data->price_with_vat = $priceVatApp + $priceVatNotApp + $vatAmount;
        } else {
            $data->price_with_vat = $priceVatApp ? ($priceVatApp + $vatAmount) : $priceVatNotApp;
        }

        $data->vat = $vatAmount;

        $oldInsuranceProviderId = $quote->insurance_provider_id;
        $newInsuranceProviderId = $data->insurance_provider_id;

        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        $businessTypeId = $quote->business_type_of_insurance_id ?? null;
        $isCreditCardEnabled = app(BrokerCommissionService::class)->isCreditCardEnabled($quoteTypeId, request()->insurance_provider_id, $businessTypeId, null, $quote);

        return DB::transaction(function () use ($quoteType, $data, $quote, $isCreditCardEnabled, $newInsuranceProviderId, $oldInsuranceProviderId) {
            $quote->update($data->toArray());

            if (ucfirst($quoteType) == QuoteTypes::CAR->value) {
                $quote->carQuoteRequestDetail->update(['insurer_quote_number' => $data->insurer_quote_number]);
            }

            $this->synchronizePaymentInformation($quote, null, $newInsuranceProviderId, $isCreditCardEnabled);

            info("Checking conditions for updating payment method for quote code: {$quote->code}", [
                'quote_type' => $quoteType,
                'source' => $quote->source,
                'old_provider' => $oldInsuranceProviderId,
                'new_provider' => $newInsuranceProviderId,
            ]);
            if ($quoteType == QuoteTypes::HOME->value && $quote->source == LeadSourceEnum::RENEWAL_UPLOAD && $newInsuranceProviderId != $oldInsuranceProviderId) {
                $this->updatePaymentMethodBasedOnInsurer($quote, $newInsuranceProviderId);
            }

            return true;
        });
    }

    private function updatePaymentMethodBasedOnInsurer($quote, $insuranceProviderId)
    {
        $insuranceProvider = app(InsuranceProviderService::class)->getEntity($insuranceProviderId);

        $insurersWithoutCCRenewal = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::EMIRATES_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
        ];

        info('Updating payment method for home renewal lead', [
            'quote_code' => $quote->code,
            'insurer' => $insuranceProvider->code,
            'payment_gateway_id' => $insuranceProvider->payment_gateway_id,
        ]);

        // Define payment method based on payment gateway
        $newPaymentMethod = in_array($insuranceProvider->code, $insurersWithoutCCRenewal) && $quote->source == LeadSourceEnum::RENEWAL_UPLOAD
            ? PaymentMethodsEnum::InsurerPayment
            : PaymentMethodsEnum::CreditCard;

        $pendingStatuses = [
            PaymentStatusEnum::PENDING,
            PaymentStatusEnum::DRAFT,
            PaymentStatusEnum::NEW,
            PaymentStatusEnum::OVERDUE,
        ];

        // Update master payment and related splits
        $payment = Payment::with('paymentSplits')
            ->whereIn('payment_status_id', $pendingStatuses)
            ->where('code', $quote->code)->first();

        if ($payment) {
            $payment->payment_methods_code = $newPaymentMethod;
            $payment->save();

            info('Updated master payment method', [
                'quote_code' => $quote->code,
                'new_method' => $newPaymentMethod,
            ]);

            // Update payment splits that are not in final status
            $payment->paymentSplits()
                ->whereIn('payment_status_id', $pendingStatuses)
                ->update([
                    'payment_method' => $newPaymentMethod,
                ]);

            info('Updated payment splits payment method', [
                'quote_code' => $quote->code,
                'new_method' => $newPaymentMethod,
            ]);
        }
    }

    public function updateSelectedPlan($quoteType, $uuid, $data)
    {
        $response = [];
        $requestData = $data;

        // switch for quote type
        switch (ucfirst($quoteType)) {
            case QuoteTypes::CAR->value:
                $endpoint = '/process-car-quote-plan';
                $data = [
                    'planId' => intval($data->plan_id),
                    'quoteTypeId' => QuoteTypeId::Car,
                    'quoteUID' => $uuid,
                    'callSource' => strtolower(LeadSourceEnum::IMCRM),
                ];
                $response = Ken::request($endpoint, 'post', $data);
                break;
            case QuoteTypes::TRAVEL->value:
                $endpoint = '/process-travel-quote-plan';
                $data = [
                    'quoteTypeId' => QuoteTypeId::Travel,
                    'quoteUID' => $uuid,
                    'callSource' => strtolower(LeadSourceEnum::IMCRM),
                    'plans' => [
                        ['id' => intval($data->plan_id), 'addonOptionIds' => []],
                    ],
                ];

                if (isset($requestData->selected_plan_id)) {
                    $data['plans'][] = ['id' => intval($requestData->selected_plan_id), 'addonOptionIds' => []];
                }

                $response = Ken::request($endpoint, 'post', $data);
                break;
            case QuoteTypes::HEALTH->value:
                $endpoint = '/api/v1-process-booking';
                $data = [
                    'planId' => intval($data->plan_id),
                    'quoteTypeId' => QuoteTypeId::Health,
                    'addonOptionIds' => [],
                    'healthPlanCoPaymentId' => intval($data->copay_id),
                    'quoteUID' => $uuid,
                    'callSource' => strtolower(LeadSourceEnum::IMCRM),
                    'url' => request()->url(),
                ];

                $response = Capi::request($endpoint, 'post', $data);
                break;
            case QuoteTypes::BIKE->value:
                $endpoint = '/process-bike-quote-plan';
                $data = [
                    'planId' => intval($data->plan_id),
                    'quoteTypeId' => QuoteTypeId::Bike,
                    'quoteUID' => $uuid,
                    'callSource' => strtolower(LeadSourceEnum::IMCRM),
                ];
                $response = Ken::request($endpoint, 'post', $data);
                break;
            case QuoteTypes::HOME->value:
                $endpoint = '/process-home-quote-plan';
                $data = [
                    'planId' => intval($data->plan_id),
                    'quoteTypeId' => QuoteTypeId::Home,
                    'quoteUID' => $uuid,
                    'callSource' => strtolower(LeadSourceEnum::IMCRM),
                ];
                $response = Ken::request($endpoint, 'post', $data);
                break;
            case QuoteTypes::SAVINGS->value:
                $endpoint = '/process-savings-quote-plan';
                $data = [
                    'planId' => intval($data->plan_id),
                    'quoteUID' => $uuid,
                    'quoteTypeId' => QuoteTypeId::Savings,
                    'callSource' => strtolower(LeadSourceEnum::IMCRM),
                ];
                $response = Ken::request($endpoint, 'post', $data);
                break;
        }

        return $response;
    }

    public function getQuoteWiseProviderPlans($quoteType, $providerId, $plandId = null): object
    {
        if ($quoteType == QuoteTypes::BIKE->value) {
            $quoteType = 'Car';
        }
        $planModel = 'App\\Models\\'.ucfirst($quoteType).'Plan';

        if ($plandId) {
            // Home Plans are fetching from home-quote-plan-details mongodb collection.
            $key = $quoteType == QuoteTypes::HOME->value ? 'planId' : 'id';

            return $planModel::where($key, (int) $plandId)->first();
        }

        return $planModel::where('provider_id', $providerId)->get();
    }

    public function getPlanById($quoteType, $planId)
    {
        $planModel = 'App\\Models\\'.ucfirst($quoteType).'Plan';

        return $planModel::find($planId);
    }

    public function lockLeadSectionsDetails($quote)
    {
        $quote = (object) $quote;
        $lockFunctionalities = [
            'plan_selection' => false,
            'plan_details' => false,
            'lead_status' => false,
            'lead_details' => false,
            'member_details' => false,
            'manage_payment' => false,
        ];

        $quoteStatuses = [
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];

        // Lock functionality check for Available Plans, Plan Details and Member Details
        $quoteStatusForPlansAndMembers = array_merge($quoteStatuses, [
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
        ]);

        if (in_array($quote->quote_status_id, $quoteStatusForPlansAndMembers)) {
            $lockFunctionalities['plan_selection'] = true;
            $lockFunctionalities['member_details'] = true;
        }

        // Lock functionality check for Lead status Section
        $lockedForQuoteStatus = [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::TransactionDeclined, QuoteStatusEnum::POLICY_BOOKING_QUEUED, QuoteStatusEnum::POLICY_BOOKING_FAILED];
        $quoteStatusForLeadStatus = array_merge($quoteStatusForPlansAndMembers, $lockedForQuoteStatus);
        if (auth()->check() && auth()->user()->can(PermissionsEnum::SUPER_LEAD_STATUS_CHANGE)) {
            in_array($quote->quote_status_id, [QuoteStatusEnum::PolicyBooked]) ? $lockFunctionalities['lead_status'] = true : $lockFunctionalities['lead_status'] = false;
        } elseif (in_array($quote->quote_status_id, $quoteStatusForLeadStatus)) {
            $lockFunctionalities['lead_status'] = true;
        }

        // Lock functionality check for edit lead details
        if (in_array($quote->quote_status_id, $quoteStatuses)) {
            $lockFunctionalities['plan_details'] = true;
            $lockFunctionalities['lead_details'] = true;
        }

        // Lock functionality check for Manage Payment
        $quoteStatusForManagePayment = array_merge($quoteStatuses, [QuoteStatusEnum::PolicyBooked]);
        if (in_array($quote->quote_status_id, $quoteStatusForManagePayment)) {
            $lockFunctionalities['manage_payment'] = true;
        }

        return $lockFunctionalities;
    }

    // This method is used to update payment allocation status when lead status is updated
    public function updatePaymentAllocation($modelType, $quote_uuid)
    {
        $quote = $this->getQuoteObjectBy($modelType, $quote_uuid, 'uuid');
        if ($quote->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            $payment = Payment::where('code', $quote->code)->with('paymentSplits')->first();
            if ($payment && $payment->paymentSplits->isNotEmpty()) {
                $this->straightforwardPayments($payment, $payment->paymentSplits, $quote);
            } else {
                LoggerService::info('Quote Code: '.$quote->code.' updatePaymentAllocation no payment found');
            }
        }
    }

    /**
     * After booking policy Processes payments by updating their allocation status based on the payment frequency and splits.
     * This method handles different payment frequencies (e.g., upfront, semi-annual, quarterly, monthly, custom, split payments)
     *
     * @param void
     */
    public function straightforwardPayments($payment, $paymentSplits, $quote)
    {
        LoggerService::info('Quote Code: '.$quote->code.' fn: straightforwardPayments');

        if ($payment) {
            $paymentSplit = $paymentSplits->first();
            $this->updatePaymentAllocationStatus($payment, $quote, $paymentSplit);
            if (in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SEMI_ANNUAL, PaymentFrequency::QUARTERLY, PaymentFrequency::MONTHLY, PaymentFrequency::CUSTOM])) {
                $this->firstSplitAllocationStatus($payment, $paymentSplit, $quote);
            }

            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $this->updatePaymentSplitAllocationStatus($paymentSplits, $quote);
            }
        }
    }

    /**
     * This method handles just update payment allocation status
     *
     * @param void
     */
    private function updatePaymentAllocationStatus($payment, $quote, $paymentSplits)
    {
        $payment->payment_allocation_status = $this->calculateAllocationStatus($payment, $quote, $paymentSplits);
        $payment->save();
    }

    /**
     * This method return payment allocation status based on quote status
     *
     * @param string
     */
    private function calculateAllocationStatus($payment, $quote, $paymentSplit = null)
    {
        $collectionAmount = $paymentSplit ? $paymentSplit->collection_amount : $payment->captured_amount;
        $priceWithVat = $quote->price_with_vat;
        LoggerService::info('Quote Code: '.$quote->code.' fn: calculateAllocationStatus sage_reciept_id: '.$paymentSplit->sage_reciept_id.' payment status id: '.$paymentSplit->payment_status_id.' payment_methods_code: '.$payment->payment_methods_code.' Split Payment method '.$paymentSplit->payment_method);

        switch (true) {
            case $paymentSplit && $paymentSplit->sage_reciept_id == null:
                LoggerService::info('Quote Code: '.$quote->code.' Condition: Payment split sage_reciept_id is set to null');

                return PaymentAllocationStatus::NOT_ALLOCATED;
            case in_array($payment->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED, PaymentStatusEnum::NEW]):
                LoggerService::info('Quote Code: '.$quote->code.' Condition: Payment status is PENDING, CREDIT_APPROVED, or NEW');

                return null;
            case $paymentSplit && in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED]):
                LoggerService::info('Quote Code: '.$quote->code.' Condition: Payment split status is PENDING or CREDIT_APPROVED');

                return PaymentAllocationStatus::NOT_ALLOCATED;
            case $collectionAmount <= 0:
                LoggerService::info('Quote Code: '.$quote->code.' Condition: Collection amount is less than or equal to 0');

                return PaymentAllocationStatus::UNPAID;
            case $collectionAmount <= $priceWithVat:
                LoggerService::info('Quote Code: '.$quote->code.' Condition: Collection amount is less than or equal to price with VAT');

                return PaymentAllocationStatus::FULLY_ALLOCATED;
            default:
                LoggerService::info('Quote Code: '.$quote->code.' Condition: Default case, partially allocated');

                return PaymentAllocationStatus::PARTIALLY_ALLOCATED;
        }
    }

    /**
     * Updates the allocation status of the first payment split based on the payment and quote details.
     * This method is specifically used for payments with frequencies like upfront, semi-annual, quarterly, monthly and custom.
     */
    private function firstSplitAllocationStatus($payment, $paymentSplit, $quote)
    {
        $paymentSplit->payment_allocation_status = $this->calculateAllocationStatus($payment, $quote, $paymentSplit);
        $paymentSplit->save();
    }

    /**
     * Updates the allocation status of the all payment  based on the payment and quote details.
     * This method is specifically used for payments with frequency split payment
     */
    private function updatePaymentSplitAllocationStatus($paymentSplits, $quote)
    {
        $collectedAmount = 0;
        foreach ($paymentSplits as $paymentSplit) {
            $collectedAmount += $paymentSplit->collection_amount;
            $paymentSplit->payment_allocation_status = $this->calculateSplitAllocationStatusWithCollectedAmount($paymentSplit, $quote, $collectedAmount);
            $paymentSplit->save();
        }
    }

    /**
     * This method is used to final payment allocation status based in payment status and collected amount and price
     */
    private function calculateSplitAllocationStatusWithCollectedAmount($paymentSplit, $quote, $collectedAmount)
    {
        if ($paymentSplit && $paymentSplit->sage_reciept_id == null) {
            return PaymentAllocationStatus::NOT_ALLOCATED;
        }

        if (in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED])) {
            return PaymentAllocationStatus::NOT_ALLOCATED;
        }

        if ($paymentSplit->collection_amount <= 0) {
            return PaymentAllocationStatus::UNPAID;
        }

        if ($collectedAmount <= $quote->price_with_vat) {
            return PaymentAllocationStatus::FULLY_ALLOCATED;
        }

        return PaymentAllocationStatus::PARTIALLY_ALLOCATED;
    }

    public function saveAndAssignActivitesToAdvisor($quoteDetails, $quoteTypeId, $previousStatusIdChanged = false)
    {
        $quoteTypeDetails = [
            CarQuote::class => [
                'eligible_for_automate' => false,
            ],
            HomeQuote::class => [
                'eligible_for_automate' => true,
                'quote_type_id' => QuoteTypeId::Home,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::HOME_RENEWALS])->first()->id,
            ],
            HealthQuote::class => [
                'eligible_for_automate' => true,
                'quote_type_id' => QuoteTypeId::Health,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::RM_RENEWALS])->first()->id,
            ],
            LifeQuote::class => [
                'eligible_for_automate' => false,
            ],
            BusinessQuote::class => [
                'eligible_for_automate' => true,
                'quote_type_id' => QuoteTypeId::Business,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::CORPLINE_RENEWALS])->first()->id,
            ],
            TravelQuote::class => [
                'eligible_for_automate' => false,
            ],
            PetQuote::class => [
                'quote_type_id' => QuoteTypeId::Pet,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::PET_RENEWALS])->first()->id,
            ],
            CycleQuote::class => [
                'quote_type_id' => QuoteTypeId::Cycle,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::CYCLE_RENEWALS])->first()->id,
            ],
            YachtQuote::class => [
                'quote_type_id' => QuoteTypeId::Yacht,
                'renewal_team' => Team::where(['type' => TeamTypeEnum::TEAM, 'name' => TeamNameEnum::YACHT_RENEWALS])->first()->id,
            ],
        ];

        $quoteTypeDetail = null;

        switch ($quoteTypeId) {
            case QuoteTypeId::Car:
                $quoteTypeDetail = $quoteTypeDetails[CarQuote::class];
                break;
            case QuoteTypeId::Home:
                $quoteTypeDetail = $quoteTypeDetails[HomeQuote::class];
                break;
            case QuoteTypeId::Health:
                $quoteTypeDetail = $quoteTypeDetails[HealthQuote::class];
                break;
            case QuoteTypeId::Life:
                $quoteTypeDetail = $quoteTypeDetails[LifeQuote::class];
                break;
            case QuoteTypeId::Business:
                $quoteTypeDetail = $quoteTypeDetails[BusinessQuote::class];
                break;
            case QuoteTypeId::Travel:
                $quoteTypeDetail = $quoteTypeDetails[TravelQuote::class];
                break;
            case QuoteTypeId::Pet:
                $quoteTypeDetail = $quoteTypeDetails[PetQuote::class];
                break;
            case QuoteTypeId::Yacht:
                $quoteTypeDetail = $quoteTypeDetails[YachtQuote::class];
                break;
            case QuoteTypeId::Cycle:
                $quoteTypeDetail = $quoteTypeDetails[CycleQuote::class];
                break;
            default:
                $quoteTypeDetail = null;
                break;
        }

        $advisorDetails = User::with('usersroles', 'teams')->where('id', $quoteDetails->advisor_id)->first();

        $lastActivity = Activities::where(
            'quote_request_id',
            $quoteDetails->id,
        )->orderBy('created_at', 'desc')->first();

        $lastActivityDueDateIsGreater = false;

        if ($lastActivity) {
            // Check if the due date is greater than today's date
            if (Carbon::parse($lastActivity->due_date)->greaterThan(now()->format('d-m-Y'))) {
                $lastActivityDueDateIsGreater = true;
            }

            // If the status ID has changed, update all activities' status for the current quote
            if ($previousStatusIdChanged || ! $lastActivity->status) {
                Activities::where('quote_request_id', $quoteDetails->id)->update(['status' => 1]);
                $lastActivityDueDateIsGreater = false;
            }

            // Check if the activity is cold or its due date is not greater than today's date
            if ($lastActivity->is_cold || Carbon::parse($lastActivity->due_date)->lessThanOrEqualTo(now()->format('d-m-Y'))) {
                Activities::where('quote_request_id', $quoteDetails->id)->update(['status' => 1]);
            }
        }

        // ->where('due_date', '<', now())

        $scheduledActivitiesIDs = Activities::where([
            'quote_request_id' => $quoteDetails->id,
            'status' => true,
        ])
            ->orderBy('created_at', 'desc')->pluck('activity_schedule_id')
            ->unique()->filter(function ($filter) {
                return ! is_null($filter);
            })->toArray();

        $getActivitySchedule = null;

        if ($advisorDetails) {
            $getActivitySchedule = ActivitySchedule::where([
                'quote_type_id' => $quoteTypeId,
                'quote_status_id' => $quoteDetails->quote_status_id,
            ])
                ->whereIn('role_id', $advisorDetails->usersroles->pluck('id'))
                ->whereIn('team_id', $advisorDetails->teams->pluck('id'))
                ->when(! empty($scheduledActivitiesIDs), function ($previousSchedule) use ($scheduledActivitiesIDs) {
                    $previousSchedule->whereNotIn('id', $scheduledActivitiesIDs);
                })
                ->when($quoteDetails->source == LeadSourceEnum::RENEWAL_UPLOAD, function ($query) use ($quoteTypeDetail) {
                    $renewalTeamID = $quoteTypeDetail['renewal_team'];

                    $query->where('team_id', $renewalTeamID ?? null);
                })
                ->orderBy('sorting_order')
                ->first();
        }

        if ($getActivitySchedule && $quoteDetails->advisor_id && ! $lastActivityDueDateIsGreater) {
            $activity = Activities::create([
                'title' => $getActivitySchedule->name,
                'description' => $getActivitySchedule->description,
                'quote_request_id' => $quoteDetails->id,
                'quote_type_id' => $quoteTypeId,
                'status' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'assignee_id' => $quoteDetails->advisor_id ?? auth()->user()->id,
                'uuid' => generateUuid(),
                'due_date' => addDaysExcludeWeekend($getActivitySchedule->due_days),
                'client_name' => $quoteDetails->first_name.' '.$quoteDetails->last_name,
                'client_email' => $quoteDetails->email,
                'quote_uuid' => $quoteDetails->uuid,
                'quote_status_id' => $quoteDetails->quote_status_id,
                'activity_schedule_id' => $getActivitySchedule->id,
                'source' => LeadSourceEnum::IMCRM,
            ]);

            return $activity;
        }

        return false;
    }

    public function getPlans($type, $id, $isRenewalSort = false, $isDisabledEnabled = false)
    {
        $quotePlans = $this->getQuotePlans($type, $id, $isRenewalSort, false, $isDisabledEnabled);
        $listQuotePlans = [];
        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if ($type == quoteTypeCode::Health) {
                if (gettype($quotePlans) != 'string') {
                    $listQuotePlans = $quotePlans->quote->plans;
                }
            } else {
                if (gettype($quotePlans) != 'string' && isset($quotePlans->quotes->plans)) {
                    $listQuotePlans = $quotePlans->quotes->plans;
                } elseif (! isset($quotePlans->quotes->plans)) {
                    $listQuotePlans = 'Plans not available!';
                } else {
                    $listQuotePlans = $quotePlans;
                }
            }
        }

        return $listQuotePlans;
    }

    public function getQuotePlans($type, $id, $isRenewalSort = false, $getLatestRating = false, $isDisabledEnabled = false)
    {
        $modelName = checkPersonalQuotes(ucfirst($type)) ? 'PersonalQuote' : ucfirst($type).'Quote';
        $model = '\\App\\Models\\'.$modelName;
        $quoteUuId = $model::where('uuid', '=', $id)->value('uuid');
        $plansApiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-'.lcfirst($type).'-quote-plans';
        $plansApiToken = config('constants.KEN_API_TOKEN');
        $plansApiTimeout = config('constants.KEN_API_TIMEOUT');
        $plansApiUserName = config('constants.KEN_API_USER');
        $plansApiPassword = config('constants.KEN_API_PWD');
        $authBasic = base64_encode($plansApiUserName.':'.$plansApiPassword);

        $plansDataArr = [
            'quoteUID' => $quoteUuId,
            'lang' => 'en',
        ];

        if ($type == quoteTypeCode::Car) {
            $plansDataArr['getLatestRating'] = $getLatestRating;
            $plansDataArr['url'] = strval(url()->current());
            $plansDataArr['ipAddress'] = request()->ip();
            $plansDataArr['userAgent'] = request()->header('User-Agent');
            $plansDataArr['userId'] = strval(auth()->id());
            $plansDataArr['filters'] = [[
                'field' => 'isRenewalSort',
                'value' => $isRenewalSort,
            ]];
            if ($isDisabledEnabled) {
                $plansDataArr['filters'][] = [
                    'field' => 'isDisabled',
                    'value' => false,
                ];
            }
        }

        $client = new \GuzzleHttp\Client;

        try {
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
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
            $contents = (string) $response->getBody();
            $response = json_decode($contents);

            LoggerService::info('fn: updateQuotePayment payment updated for quote uuid: '.$quoteUuId);
        }
    }

    public function lockTransactionStatus($quote, $quoteTypeId, $quoteStatuses)
    {
        $lockLeadStatus = $this->lockLeadSectionsDetails($quote);
        if ($lockLeadStatus['lead_status'] || auth()->user()->can(PermissionsEnum::SUPER_LEAD_STATUS_CHANGE)) {
            return $quoteStatuses;
        }

        $lockedQuotesStatuses = [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::TransactionDeclined,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
            QuoteStatusEnum::POLICY_BOOKING_QUEUED,
            QuoteStatusEnum::POLICY_BOOKING_FAILED,
        ];

        $isTransactionApproved = QuoteStatusLog::where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $quote->id)
            ->where(function ($query) {
                $query->where('current_quote_status_id', QuoteStatusEnum::TransactionApproved)
                    ->orWhere('previous_quote_status_id', QuoteStatusEnum::TransactionApproved);
            })
            ->count();

        if (! $isTransactionApproved) {
            $quoteStatuses = collect($quoteStatuses)->filter(function ($value) use ($lockedQuotesStatuses) {
                return ! in_array($value['id'], $lockedQuotesStatuses);
            })->values();
        }

        return $quoteStatuses;
    }

    public function updateSendUpdateStatusLogs($sendUpdateLogId, $previousStatus, $currentStatus): void
    {
        LoggerService::info('fn:updateSendUpdateStatusLogs - Start - CentralService');

        SendUpdateStatusLog::updateOrCreate([
            'send_update_log_id' => $sendUpdateLogId,
            'previous_status' => $previousStatus,
            'current_status' => $currentStatus,
        ], [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        LoggerService::info('SendUpdateLog status changed', extra: [
            'previousStatus' => $previousStatus,
            'current_status' => $currentStatus,
        ]);
    }

    public function checkStatusSUStatusLogs($sendUpdateId, $sendUpdateStatus): bool
    {
        LoggerService::info('fn:checkStatusSUStatusLogs - Start - CentralService');
        $sendUpdateStatusArray = is_string($sendUpdateStatus) ? [$sendUpdateStatus] : $sendUpdateStatus;

        $sendUpdateStatusCount = SendUpdateStatusLog::where(function ($query) use ($sendUpdateId, $sendUpdateStatusArray) {
            $query->where('send_update_log_id', $sendUpdateId)
                ->where(function ($query) use ($sendUpdateStatusArray) {
                    $query->whereIn('current_status', $sendUpdateStatusArray)
                        ->orWhereIn('previous_status', $sendUpdateStatusArray);
                });
        })->count();

        return $sendUpdateStatusCount > 0;
    }

    /**
     * This method is used to check if the COMMISSION (VAT NOT APPLICABLE) is enabled or not.
     *
     * @param  $quoteType  - Life, Business etc.
     * @param  $businessTypeOfInsuranceId  - Business type of insurance id, if quote type is Business.
     */
    public function commissionVatNotApplicableEnabled($quoteType, $businessTypeOfInsuranceId = null): bool
    {
        if (
            ($quoteType == quoteTypeCode::Business &&
                in_array($businessTypeOfInsuranceId, [
                    quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineCargoIndividual),
                    quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineHull),
                    quoteBusinessTypeCode::getId(quoteBusinessTypeCode::marineCargoOpenCover),
                    quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupLife),
                ])) ||
            $quoteType == quoteTypeCode::Life
        ) {
            return true;
        }

        return false;
    }

    public function generateExportLogs(): void
    {
        try {
            $exportLogs = QuoteExportLog::create([
                'type' => ExportLogsTypeEnum::SEARCH_MODULE,
                'quote_type_id' => request()->quote_type_id ?? null,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'url' => request()->fullUrl(),
            ]);
            LoggerService::info('fn: generateExportLogs export log created');
        } catch (\Exception $e) {
            LoggerService::error('fn: generateExportLogs error: '.$e->getMessage());
        }
    }

    public function synchronizePaymentInformation($quoteObject, $sendUpdatePayment = null, $insuranceProviderId = null, $isCreditCardEnabled = true, $isHomeRenewalLead = false)
    {
        LoggerService::info('Quote Code: '.$quoteObject->code.' fn: synchronizePaymentInformation called');
        if (! $sendUpdatePayment) {
            $payment = $quoteObject->payments()->mainLeadPayment()->first();
        } else {
            $payment = $sendUpdatePayment;
        }
        if ($payment) {
            if ($insuranceProviderId) {
                $payment->insurance_provider_id = $insuranceProviderId;
            }
            app(PaymentService::class)->processMasterPayment($payment, $quoteObject, $isCreditCardEnabled);
            app(SplitPaymentService::class)->updateSplitPaymentStatusAndAmount($payment, $isCreditCardEnabled);

            return $this->isLackingPayment($payment);
        }
    }

    /**
     * Updates quote & policy issuance status, first will check if the quote's current status is not already set to 'Policy Sent to Customer'
     * We check policy issuance status is not 'Policy Issued' & if afilled policy details & required documents are uploaded
     * This will trigger once policy details section update or new document upload from upload document section
     */
    public function updateQuoteInformation($type, $id)
    {
        if ($type == 'send-update') {
            return;
        }
        if (request()->has('quote_type')) {
            $type = request()->quote_type;
        }

        $quote = $this->getQuoteObject($type, $id);
        $quoteCode = $quote->code;
        $currentQuoteStatus = $quote->quote_status_id;
        // Check if quote status is locked - if so, don't change status due to document uploads
        if ($this->isQuoteStatusLocked($quote)) {
            LoggerService::info("Quote Code: {$quoteCode} - Status is LOCKED {$currentQuoteStatus}, preventing document uploads from changing status");

            return;
        }

        $isPolicyDetailsFilled = $this->isFilledPolicyDetails($type, $quote);
        LoggerService::info("Quote Code: {$quoteCode} - Policy details filled: ".($isPolicyDetailsFilled ? 'YES' : 'NO'));

        if ($isPolicyDetailsFilled) {
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($type));

            if ($this->canUpdateToPolicyIssued($type, $id, $quote, $quoteTypeId)) {
                $previousQuoteStatus = $quote->quote_status_id;
                $updateData = [
                    'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                    'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
                    'policy_issuance_status_other' => '',
                ];

                $quote->update($updateData);

                LoggerService::info("Quote Code: {$quoteCode} - Status updated: {$previousQuoteStatus} → {$quote->quote_status_id}");

                // Create status log and trigger journey if status actually changed
                if ($previousQuoteStatus != $quote->quote_status_id) {
                    app(QuoteStatusLogService::class)->createQuoteStatusLog($quoteTypeId, $quote, $previousQuoteStatus);
                    (new QuoteJourneyService)->policyIssuedQuoteJourney($quote->uuid, $quoteTypeId);
                    LoggerService::info("Quote Code: {$quoteCode} - Status log created and journey triggered");
                } else {
                    LoggerService::info("Quote Code: {$quoteCode} - No status change, skipping log creation");
                }
            } else {
                LoggerService::info("Quote Code: {$quoteCode} - Cannot update to Policy Issued, requirements not met");
            }
        } else {
            LoggerService::info("Quote Code: {$quoteCode} - Policy details not filled, skipping status update");
        }
    }

    /**
     * Check if quote is in a locked status that prevents document uploads from changing status
     */
    private function isQuoteStatusLocked($quote): bool
    {
        $statusesThatPreventDocumentUploads = [
            QuoteStatusEnum::PolicyIssued,
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
            // These statuses prevent document uploads from changing quote status
            QuoteStatusEnum::POLICY_BOOKING_QUEUED,
            QuoteStatusEnum::POLICY_BOOKING_FAILED,
        ];

        return in_array($quote->quote_status_id, $statusesThatPreventDocumentUploads);
    }

    /**
     * Determine if a quote can be updated to Policy Issued status.
     */
    private function canUpdateToPolicyIssued($type, $id, $quote, $quoteTypeId): bool
    {
        $quoteCode = $quote->code;

        // First, check if all required documents are uploaded (most expensive check first)
        $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments($type, $id);
        $hasAllRequiredDocuments = app(QuoteDocumentService::class)->areDocsUploaded($quoteDocuments, $type, $quote);

        LoggerService::info("Quote Code: {$quoteCode} - Document check: Required docs uploaded=".($hasAllRequiredDocuments ? 'YES' : 'NO'));

        // If documents are not uploaded, no need to check other conditions
        if (! $hasAllRequiredDocuments) {
            return false;
        }

        // Only check transaction approved status if documents are uploaded
        $hasTransactionApprovedHistory = app(QuoteStatusLogService::class)->hasTransactionApprovedStatus($quoteTypeId, $quote->id);
        $isCurrentlyTransactionApproved = $quote->quote_status_id === QuoteStatusEnum::TransactionApproved;

        return $hasTransactionApprovedHistory || $isCurrentlyTransactionApproved;
    }

    /**
     * Get the TAP configuration for a given quote & send update.
     *
     * @param  string  $quoteType  The type of the quote.
     * @param  object  $quote  The quote object or send update object.
     * @param  object|null  $payment  The payment object (optional).
     * @param  bool|null  $isTapProcessCheck  Flag to check if TAP capture process should start (optional).
     * @return array The TAP configuration.
     */
    public function getTapConfiguration($quoteType, $quote, $payment = null, $isTapProcessCheck = null, $sendUpdateLog = null)
    {
        // Retrieve necessary IDs from the quote object
        $businessTypeId = $quote->business_type_of_insurance_id ?? null;

        $allowedQuoteTypes = [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value, QuoteTypes::TRAVEL->value, QuoteTypes::BIKE->value];
        if ($payment && in_array(ucfirst($quoteType), $allowedQuoteTypes)) {
            $planId = $payment->plan_id ?? null;
        } else {
            $planId = $quote->plan_id ?? null;
        }
        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);

        // Get insurance provider details
        $insuranceProvider = getInsuranceProvider($payment, $quoteType, $quote);
        $insuranceProviderId = $insuranceProvider ? $insuranceProvider->id : null;

        // Get broker commission details
        [$isCreditCardEnabled, $brokerCommission, $commissionInPayments] = app(BrokerCommissionService::class)->fetchBrokerCommission($quoteTypeId, $insuranceProviderId, $businessTypeId, $planId, $quote);

        $isGIGProvider = $insuranceProvider && $insuranceProvider->code === InsurerProviderEnum::GIG_INSURANCE;
        $isADNICProvider = $insuranceProvider && $insuranceProvider->code === InsurerProviderEnum::ABU_DHABI_NATIONAL_INSURANCE && $quoteTypeId == QuoteTypeId::Health;

        // Check if multiple payments are enabled for the provider
        $isMultiplePaymentsEnabled = $insuranceProvider && $insuranceProvider->multiple_payments;

        $sendUpdateLogId = null;
        if ($sendUpdateLog) {
            $sendUpdateLogId = $sendUpdateLog->id;
        }

        // Check if TAP capture process should start
        $isTapCaptureProcessStart = $isTapProcessCheck ? app(QuoteTagService::class)->isCapturePaymentStarted($quote->uuid, $quoteTypeId, $sendUpdateLogId) : false;

        // Prepare the TAP configuration array
        $tapConfiguration = [
            'isCreditCardEnabled' => $isCreditCardEnabled,
            'brokerCommission' => $brokerCommission,
            'isGIGProvider' => $isGIGProvider,
            'isTapCaptureProcessStart' => $isTapCaptureProcessStart,
            'isMultiplePaymentsEnabled' => $isMultiplePaymentsEnabled,
            'commissionInPayments' => $commissionInPayments,
            'isADNICProvider' => $isADNICProvider,
            'isCaptureButtonEnabled' => true,
        ];

        // If payment object is provided, check commission status and merge with TAP configuration
        if ($payment) {
            $commissionInfo = app(SplitPaymentService::class)->checkCommissionStatus($payment);
            $tapConfiguration = array_merge($tapConfiguration, $commissionInfo);
        }

        return $tapConfiguration;
    }

    public function voidPayment($request): array
    {
        $paymentCode = $request->payment_code;
        LoggerService::info('fn:voidPayment - Void authorized payment process started for payment code: '.$paymentCode);
        $payment = Payment::where('code', $paymentCode)->first();
        if (! $payment) {
            LoggerService::info('fn:voidPayment - Payment not found. - Payment Code:'.$paymentCode);

            return ['status' => false, 'message' => 'Payment not found'];
        }

        $paymentAgainst = $request->send_update_log_id ? 'Send Update' : 'Main Lead';
        LoggerService::info('fn:voidPayment - Payment found against '.$paymentAgainst.' - Payment Code:'.$paymentCode);
        $paymentGateways = [
            PaymentGatewayIdEnum::PAYMENT_GATEWAY_CHECKOUT => PaymentGatewayIdEnum::PAYMENT_GATEWAY_CHECKOUT_TEXT,
            PaymentGatewayIdEnum::PAYMENT_GATEWAY_TAP => PaymentGatewayIdEnum::PAYMENT_GATEWAY_TAP_TEXT,
        ];

        if ($payment->payment_gateway_id !== PaymentGatewayIdEnum::PAYMENT_GATEWAY_TAP) {
            LoggerService::info('fn:voidPayment - Payment gateway not supported - Payment Gateway: '.$paymentGateways[$payment->payment_gateway_id].' Payment Code:'.$paymentCode);

            return ['status' => false, 'message' => 'Payment gateway not supported'];
        }

        $voidPaymentURL = '/payment/'.$paymentGateways[$payment->payment_gateway_id].'/cancel';
        $payload = [
            'quoteUID' => $request->quote_uuid,
            'quoteTypeId' => (int) $request->quote_type_id,
            'payments' => [
                [
                    'codeRef' => $paymentCode,
                ],
            ],
        ];

        if ($request->send_update_log_id) {
            $sendUpdateLog = SendUpdateLog::where('id', $request->send_update_log_id)->first();
            $payload['quoteUID'] = $sendUpdateLog->uuid;
            $payload['quoteTypeId'] = GenericRequestEnum::SEND_UPDATE_QUOTE_TYPE_MARSHAL;
        }

        $response = Marshall::request($voidPaymentURL, 'post', $payload);
        LoggerService::info('fn:voidPayment - Payment Code:'.$paymentCode.' - Payment Gateway:'.$paymentGateways[$payment->payment_gateway_id].' - void payment - payload:'.json_encode($payload).' - response:'.json_encode($response));

        if (! empty($response)) {
            LoggerService::info('fn:voidPayment - Void authorized payment process failed for payment code: '.$paymentCode);

            return ['status' => false, 'message' => 'Something went wrong'];
        }

        LoggerService::info('fn:voidPayment - Void authorized payment process completed for payment code: '.$paymentCode);

        return ['status' => true, 'message' => 'Void payment processed'];
    }

    /**
     * Send automation email to Bird
     *
     * @param  $emailData  | should be object
     * @return int|null
     */
    public function sendAutomationEmail($lead, $emailData, $quoteTypeId, $emailType)
    {
        LoggerService::startQuoteLogging($lead);
        $quoteType = strtoupper(QuoteTypes::getName($quoteTypeId)->value);

        try {
            LoggerService::info("Sending {$quoteType} followups email for {$emailType} uuid: ".$lead->uuid.' | Time: '.now());
            $birdUrlKey = ApplicationStorageEnums::BIRD_AUTOMATION_WORKFLOW_URL;

            $birdUrl = ApplicationStorage::where('key_name', $birdUrlKey)->first();
            if ($birdUrl) {
                $response = app(BirdService::class)->triggerWebHookRequest($birdUrl?->value, $emailData);
                LoggerService::info("{$quoteType} response: ".json_encode($response)." | {$emailType} uuid: {$lead->uuid} |Time: ".now());

                if (! empty($response->headers['Run-Id'])) {
                    $this->createQuoteFlowDetails($lead, $response, $quoteTypeId, $emailType, strtoupper($emailData->workflowType));
                }
            } else {
                LoggerService::info("{$birdUrlKey} key not found for {$emailType} uuid: {$lead->uuid} |Time: ".now());
            }

            return $response?->status_code ?? null;
        } catch (\Exception $ex) {
            $errorMessage = "{$birdUrlKey}-Error: while sending quote workflow for {$emailType}: uuid: {$lead->uuid} | Time: ".now();
            LoggerService::info($errorMessage);
            LoggerService::info("{$birdUrlKey}-Error: {$ex->getMessage()} | uuid: {$lead->uuid} | Time: ".now());
        }
    }

    public function createQuoteFlowDetails($lead, $response, $quoteTypeId, $emailType, $workflowType)
    {
        try {
            $flowType = constant("App\Enums\QuoteFlowType::{$workflowType}");

            $runId = collect($response->headers['Run-Id'])->first();
            if (! empty($runId)) {
                QuoteFlowDetails::create([
                    'quote_uuid' => $lead->uuid,
                    'quote_type_id' => $quoteTypeId,
                    'flow_type' => $flowType,
                    'flow_id' => $runId,
                ]);
                LoggerService::info("{$emailType} run id created for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            } else {
                LoggerService::info("{$emailType} run id not found for lead : Ref-ID: {$lead->uuid} |Time: ".now());
            }
        } catch (\Exception $ex) {
            $errorMessage = "{$emailType}-Error: while creating quote flow details for lead: Ref-ID: {$lead->uuid} | Time: ".now();
            LoggerService::info($errorMessage);
            LoggerService::info("{$emailType}-Error: {$ex->getMessage()} | Ref-ID: {$lead->uuid} | Time: ".now());
        }
    }

    public function removeInsurerPaymentLink($request)
    {
        $quote = $this->getQuoteObject($request->quoteType, $request->quoteId);
        if (! $quote) {
            return ['status' => false, 'message' => 'Quote not found'];
        }

        $quote->quote_status_id = QuoteStatusEnum::InNegotiation;
        $quote->save();

        $paymentSplits = method_exists($quote, 'getAllInsurerPaymentLinkSplits') ? $quote->getAllInsurerPaymentLinkSplits() : [];
        foreach ($paymentSplits as $ps) {
            $ps->insurer_payment_link = null;
            $ps->save();
        }

        return ['status' => true, 'message' => 'Insurer payment link removed'];
    }

    // Todo: This method will remove in future if Business confirm we will enable capture of all providers
    private function isCaptureButtonEnabledForProvider($insuranceProviderCode, $quoteTypeId)
    {
        // Capture are enabled for the all LOB's against specific providers
        $enabledProviders = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::RAK_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::ALLIANCE_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
        ];

        if ($quoteTypeId == QuoteTypeId::Health) {
            $enabledProviders[] = InsurerProviderEnum::ABU_DHABI_NATIONAL_INSURANCE;
        }

        // if ($quoteTypeId == QuoteTypeId::Car) {
        //     $enabledProviders[] = InsurerProviderEnum::WATANIA_TAKAFUL;
        // }

        if ($quoteTypeId == QuoteTypeId::Travel) {
            $enabledProviders[] = InsurerProviderEnum::ORIENT_INSURANCE;
        }

        return in_array($insuranceProviderCode, $enabledProviders);
    }

    public function capturePaymentValidation($uuid, $quoteTypeId, $captureAmount, $quoteCode)
    {
        try {
            $data = [
                'quoteUID' => $uuid,
                'quoteTypeId' => $quoteTypeId,
                'captureAmount' => $captureAmount,
            ];

            return Ken::request('/capture-payment-validation', 'put', $data);
        } catch (\Throwable $th) {
            LoggerService::error('capturePaymentValidation failed',
                context: [
                    'ref_id' => $quoteCode,
                ],
                extra: [
                    'quoteTypeId' => $quoteTypeId,
                    'captureAmount' => $captureAmount,
                ],
                exception: $th);

            return ['status' => 'CAPTURE_VALIDATION_FAILED', 'message' => $th->getMessage()];
        }
    }

    public function deletePayment($request): array
    {
        $paymentCode = $request->payment_code;
        LoggerService::info('fn:deletePayment - process started: '.$paymentCode);

        $payment = Payment::where(
            [
                'id' => $request->payment_id,
                'code' => $request->payment_code,
                'paymentable_type' => TravelQuote::class,
            ])
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::PENDING,
                PaymentStatusEnum::NEW,
                PaymentStatusEnum::DRAFT,
                PaymentStatusEnum::OVERDUE,
            ])
            ->first();
        if (! $payment) {
            LoggerService::info('fn:deletePayment - Payment not found: '.$paymentCode);

            return ['status' => false, 'message' => 'Payment not found'];
        }

        $quote = $payment->paymentable;
        $aboveAgeMembers = app(TravelQuoteService::class)->getAboveAgeMembers($quote->id);
        if ($quote->payments()->count() < 2 || ! $aboveAgeMembers) {
            LoggerService::info('fn:deletePayment - Payment cannot be deleted: '.$paymentCode);

            return ['status' => false, 'message' => 'Payment cannot be deleted'];
        }

        // Delete Payment and Payment Splits
        try {
            $maxAttempts = 2;
            $this->handleWithDeadlockRetries(function () use ($request) {
                $paymentSplits = PaymentSplits::where('code', $request->payment_code)->get();
                foreach ($paymentSplits as $paymentSplit) {
                    $paymentSplit->documents()->forceDelete();
                }
                PaymentSplits::where('code', $request->payment_code)->delete();
                PaymentStatusHistory::where('payment_code', $request->payment_code)->delete();
                Payment::where('id', $request->payment_id)->delete();
            }, $maxAttempts);
            info('fn:deletePayment - Payment deleted successfully: '.$paymentCode);
        } catch (\Throwable $th) {
            info('fn:deletePayment - Payment deletion failed: '.$paymentCode);

            return ['status' => false, 'message' => 'Payment deletion failed'];
        }

        return ['status' => true, 'message' => 'Delete payment processed'];
    }

    public function getPlansPaymentGateway($request, $quoteType)
    {
        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        $paymentGatewayIds = [];
        foreach ($request->plan_ids as $plan) {
            $planId = $plan['planId'];
            $providerId = $plan['providerId'];

            if (! $planId || ! $providerId) {
                continue;
            }

            $childPaymentGatewayIds = ['plan_id' => $planId, 'gateway_id' => 3];

            // COMMENTED FOR NOW WILL BE USED LATER WHEN BROKER COMMISSION CHANGES GO LIVE
            // // First check if Broker Commission exists for the plan+provider+quoteTypeId
            // $planBrokerCommission = BrokerCommission::where(['plan_id' => $planId, 'insurance_provider_id' => $providerId, 'quote_type_id' => $quoteTypeId, 'is_active' => 1])->first();
            // if($planBrokerCommission) {
            //     $childPaymentGatewayIds['gateway_id'] = $planBrokerCommission->enable_payment_link ? 4:3;
            //     $paymentGatewayIds[] = $childPaymentGatewayIds;
            //     continue;
            // }

            // // Second check if Broker Commission exists for the provider+quoteTypeId
            // $providerBrokerCommission = BrokerCommission::where(['insurance_provider_id' => $providerId, 'quote_type_id' => $quoteTypeId, 'is_active' => 1])->whereNull('plan_id')->first();
            // if($providerBrokerCommission) {
            //     $childPaymentGatewayIds['gateway_id'] = $providerBrokerCommission->enable_payment_link ? 4:3;
            //     $paymentGatewayIds[] = $childPaymentGatewayIds;
            //     continue;
            // }

            // Third check from insurance_provider table
            $insuranceProvider = InsuranceProvider::where(['id' => $providerId, 'is_active' => 1])->first();

            if ($insuranceProvider) {
                $childPaymentGatewayIds['gateway_id'] = $insuranceProvider->payment_gateway_id;
            }

            $paymentGatewayIds[] = $childPaymentGatewayIds;
        }

        return $paymentGatewayIds;
    }

    public function updateBookingDetails($validatedData, $bookPolicyRequest)
    {
        $quote = $this->getQuoteObject($validatedData['model_type'], $validatedData['quote_id']);
        $paymentInformation = [
            'insurer_tax_number' => $validatedData['insurer_tax_invoice_number'],
            'transaction_payment_status' => $validatedData['transaction_payment_status'],
            'insurer_commmission_invoice_number' => $validatedData['insurer_commmission_invoice_number'],
            'broker_invoice_number' => $validatedData['broker_invoice_number'],
            'insurer_invoice_date' => $validatedData['invoice_date'],
            'commission_vat_not_applicable' => $validatedData['commission_vat_not_applicable'],
            'commission_vat_applicable' => $validatedData['commission_vat_applicable'],
            'commmission_percentage' => $validatedData['commission_percentage'],
            'commission_vat' => $validatedData['vat_on_commission'],
            'commission' => $validatedData['total_commission'],
            'invoice_description' => $validatedData['invoice_description'],

            // for life only
            'commission_based_on_currency' => $bookPolicyRequest?->commission_based_on_currency ?? null,
            'exchange_rate' => $bookPolicyRequest?->exchange_rate ?? null,
            'currency' => $bookPolicyRequest?->currency ?? null,
        ];

        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->first();

        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = Payment::where([
                'paymentable_id' => $quote->id,
                'paymentable_type' => $quote->getMorphClass(),
            ])->mainLeadPayment()->first();
        }

        $payment->update($paymentInformation);
        LoggerService::info('Quote Code: '.$validatedData['payment_code'].' Book policy details update successfully');

        $response = (new SplitPaymentService)->updateCommissionSchedule($payment);

        if (! $response['status']) {
            return ['status' => false, 'message' => $response['message']];
        }

        LoggerService::info('Quote Code: '.$validatedData['payment_code'].' Commission Schedule updated successfully');

        return ['status' => true, 'message' => 'Book policy details update successfully'];
    }

    public function autoCapturePaymentProcess($quoteTypeId, $quote, $premiumCheckEnabled = true)
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->first();
        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType->code);

        LoggerService::info(__FUNCTION__.' - Auto capture payment process started', extra: ['paymentCode' => $payment->code]);

        if (! app(AMLService::class)->autoCaptureAMLValidationCheck($quote)) {
            LoggerService::info('fn:autoCaptureAMLValidationCheck failed - Going to dispatch AutomationFailedJob');
            AutomationFailedJob::dispatch(
                $quote,
                QuoteTypeId::Car,
                'Please liaise with the Insurer UW or Insurar Portal to resolve the rejection.',
                'Quote Referred To Insurer UW',
                'Payment Capture',
                WorkflowTypeEnum::CAR_AUTOMATION_FAILED
            )->onQueue('policy-issuance-automation');

            return ['status' => false, 'message' => 'Auto capture payment process failed', 'autoCaptureStatus' => GenericRequestEnum::FAILED, 'autoCaptureMessage' => 'Auto capture payment process failed due to AML Screening Failed'];
        }

        if ($premiumCheckEnabled) {
            // Premium check call to check if the premium is valid
            $capturePaymentResponse = $this->capturePaymentValidation($quote->uuid, $quoteType->id, $payment->total_amount, $quote->code);
            $logExtra = [
                'paymentCode' => $payment->code,
                'quoteTypeId' => $quoteType->id,
                'responseStatus' => isset($capturePaymentResponse['status']) ? $capturePaymentResponse['status'] : null,
                'responseMessage' => isset($capturePaymentResponse['message']) ? $capturePaymentResponse['message'] : null,
                'responsePremiumAmount' => isset($capturePaymentResponse['premiumAmount']) ? $capturePaymentResponse['premiumAmount'] : null,
            ];

            // responsePremiumAmount (GetQuote : (Premium >  Total Price) or (Premium <  Total Price)) in this case line 7 validation text
            // responsePremiumAmount GetQuote: UW = N & Premium >  Total Price in this case line 8 validation text

            if ($capturePaymentResponse['status'] == PaymentCaptureValidationEnum::FAILED) {
                LoggerService::info(__FUNCTION__.' - paymentsCaptureValidation check for Insurance Provider: '.$insuranceProvider->text.' failed', extra: $logExtra);

                // TODO:: This should be dynamic as per insurance provider and need to check with API team about the response message
                // $messages = [
                //     'Capture amount exceeds the authorized amount' => 'Capture amount in IMCRM and either is greater than Authorized amount',
                //     'Capture amount exceeds the authorized amount and differs from premium in GIG portal' => 'Capture amount in IMCRM and both are greater than Authorized amount',
                //     'Premium mismatch with GIG portal' => 'Capture amount in IMCRM and both are less than or equal to Authorized amount',
                //     'Premium in GIG portal exceeds the authorized amount and differs from capture amount' => 'Capture amount in IMCRM and getQuote premium is greater than Authorized amount, but the Capture amount is less than or equal to the Authorized amount',
                //     'Capture amount exceeds authorized amount and differs from premium in GIG  portal' => 'Capture amount in IMCRM and getQuote premium is less than or equal to the Authorized amount, but the Capture amount is greater than Authorized amount',
                // ];

                if ($capturePaymentResponse['premiumAmount'] > $payment->total_amount) {
                    LoggerService::info('fn:autoCapturePaymentProcess - Going to dispatch AutomationFailedJob');
                    AutomationFailedJob::dispatch(
                        $quote,
                        QuoteTypeId::Car,
                        'Please liaise with the Insurer UW or Insurar Portal to resolve the rejection.',
                        'Premium Not Matched With Insurer',
                        'Payment Capture',
                        WorkflowTypeEnum::CAR_AUTOMATION_FAILED
                    )->onQueue('policy-issuance-automation');
                } elseif ($capturePaymentResponse['premiumAmount'] != $payment->total_amount) {
                    LoggerService::info('fn:autoCapturePaymentProcess - Going to dispatch AutomationFailedJob');
                    AutomationFailedJob::dispatch(
                        $quote,
                        QuoteTypeId::Car,
                        'Please coordinate with the Insurer\'s Portal for any discrepancies or changes in the premium.',
                        'Quote Referred To Insurer UW',
                        'Payment Capture',
                        WorkflowTypeEnum::CAR_AUTOMATION_FAILED
                    )->onQueue('policy-issuance-automation');
                }

                $message = $capturePaymentResponse['message'] ?? 'Premium mismatch on Insurer portal';

                return ['status' => false, 'message' => $message, 'autoCaptureStatus' => GenericRequestEnum::FAILED, 'autoCaptureMessage' => 'Auto capture payment process failed due to '.$message];
            }

            LoggerService::info(__FUNCTION__.' - paymentsCaptureValidation check for Insurance Provider: '.$insuranceProvider->text.' success', extra: $logExtra);
        }

        $paymentSplits = $payment->paymentSplits;
        $collectionAmount = $paymentSplits->pluck('premium_authorized', 'sr_no')->toArray();

        $splitPaymentApprovalRequest = new SplitPaymentApproveRequest([
            'modelType' => $quoteType->code,
            'quote_id' => $quote->id,
            'plan_id' => $payment->plan_id,
            'payment_code' => $payment->code,
            'customer_id' => $quote->customer_id,
            'collection_amount' => $collectionAmount,
            'is_declined' => 0,
            'is_capture' => 1,
            'is_approved' => 0,
            'declined_reason' => $payment->declined_reason,
            'send_update_id' => null,
            'collection_type' => $payment->collection_type,
        ]);

        $response = app(PaymentRepository::class)->handlePaymentApprove($splitPaymentApprovalRequest);
        LoggerService::info(__FUNCTION__.' - Split payment approval process completed', extra: ['paymentCode' => $payment->code]);

        if (is_string($response)) {
            return ['message' => $response, 'autoCaptureStatus' => GenericRequestEnum::SUCCESS, 'autoCaptureMessage' => 'Auto capture payment process started'];
        }

        $response['autoCaptureStatus'] = GenericRequestEnum::SUCCESS;
        $response['autoCaptureMessage'] = 'Auto capture payment process started';

        return $response;
    }

    public function checkInsurerReceiptNumber($quoteType, $receiptNumber)
    {
        $count = PaymentSplits::where('insurer_receipt_number', $receiptNumber)->count();
        if ($count > 0) {
            LoggerService::info('fn:checkInsurerReceiptNumber - Receipt number already exists: '.$receiptNumber);

            return ['status' => false, 'message' => 'Receipt number already exists'];
        }

        LoggerService::info('fn:checkInsurerReceiptNumber - Receipt number does not exist: '.$receiptNumber);

        return ['status' => true, 'message' => 'Receipt number does not exist'];
    }
}
