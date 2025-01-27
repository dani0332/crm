<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\ExportLogsTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentFrequency;
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
use App\Facades\Capi;
use App\Facades\Ken;
use App\Models\Activities;
use App\Models\ActivitySchedule;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\CycleQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use App\Models\QuoteBatches;
use App\Models\QuoteExportLog;
use App\Models\QuoteStatusLog;
use App\Models\SendUpdateStatusLog;
use App\Models\Team;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use App\Repositories\PersonalQuoteRepository;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CentralService extends BaseService
{
    use GenericQueriesAllLobs, TeamHierarchyTrait;

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

        $repository = $this->getRepositoryObject($parentType);
        $parentRecord = $repository::where('id', $entityId)->first();

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
                    $lob = quoteTypeCode::Business;
                    $dataArr['businessTypeOfInsuranceId'] = $parentRecord->business_type_of_insurance_id ?? '';

                    if (strtolower($lob) == strtolower(quoteTypeCode::GroupMedical)) {
                        $dataArr['businessTypeOfInsuranceId'] = QuoteTypeId::Business;
                    }
                }

                $repository = $this->getRepositoryObject(ucfirst($lob));

                if (! class_exists($repository)) {
                    return false;
                }

                $response = ((method_exists($repository, 'fetchCreateDuplicate') && ! checkPersonalQuotes(ucfirst($lob))) ? $repository::createDuplicate($dataArr) : PersonalQuoteRepository::createDuplicate($dataArr, ucfirst($lob)));

                if (empty($response) || (isset($response->message) && str_contains($response->message, 'Error'))) {
                    $resp['errors'][] = 'Something went wrong while duplicating '.$lob.' quotes';
                } elseif (isset($response->quoteUID) && isset($parentRecord->enquiryType) && $parentRecord->enquiryType == GenericRequestEnum::RECORD_PURPOSE) {
                    $record = $repository::where('uuid', $response->quoteUID)->first();
                    if ($record) {
                        $update = [
                            'parent_duplicate_quote_id' => $parentRecord->code,
                            'advisor_id' => auth()->user()->id,
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
        $personalQuotes = [quoteTypeCode::Bike, quoteTypeCode::Cycle, quoteTypeCode::Pet, quoteTypeCode::Yacht, quoteTypeCode::Jetski];
        $quoteBatch = QuoteBatches::latest()->first();
        Log::info('Leads ids to assign: '.json_encode($leadsIds).' Quote Batch with ID: '.$quoteBatch->id.' and Name: '.$quoteBatch->name);

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

    public function loadAvailablePlans($type, $id, $isRenewalSort = false, $isDisabledEnabled = false)
    {
        $type = ucfirst($type);
        switch ($type) {
            case quoteTypeCode::Car:
                return app(CarQuoteService::class)->getPlans($id);
            case quoteTypeCode::Travel:
                return app(TravelQuoteService::class)->sortedPlansList($id);
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
            default:
                return [];
        }
    }

    public function updateQuotePayment($quote, $priceWithVat, $insuranceProviderId)
    {
        info('fn: updateQuotePayment called');

        if ($quote->payments()->count() > 0) {
            info('fn: updateQuotePayment payment found to be updated for quote uuid: '.$quote->uuid);

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

            info('fn: updateQuotePayment payment updated for quote uuid: '.$quote->uuid);
        }
    }

    /**
     * @return true
     */
    public function savePlanDetails($quoteType, $code, $data)
    {
        return DB::transaction(function () use ($quoteType, $code, $data) {
            $vatPercentage = ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0;
            $repository = getRepositoryObject($quoteType);

            $priceVatApp = $data->price_vat_applicable ?? 0;
            $priceVatNotApp = $data->price_vat_not_applicable ?? 0;

            if ($quoteType == QuoteTypes::BUSINESS->value) {
                $data->price_with_vat = ($priceVatApp + $priceVatNotApp) + (($priceVatApp / 100) * $vatPercentage);
            } else {
                $data->price_with_vat = $priceVatApp ? ($priceVatApp + (($priceVatApp / 100) * $vatPercentage)) : $priceVatNotApp;
            }

            $quote = $repository::where('code', $code)->firstOrFail();

            $quote->update($data->toArray());
            $this->synchronizePaymentInformation($quote, null, $data->insurance_provider_id);

            return true;
        });
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
            return $planModel::find($plandId);
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
        $quote = $this->getQuoteObject($modelType, $quote_uuid);
        if ($quote->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            $payment = Payment::where('code', $quote->code)->with('paymentSplits')->first();
            if ($payment && $payment->paymentSplits->isNotEmpty()) {
                $this->straightforwardPayments($payment, $payment->paymentSplits, $quote);
            } else {
                info('Quote Code: '.$quote->code.' updatePaymentAllocation no payment found');
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
        info('Quote Code: '.$quote->code.' fn: straightforwardPayments');

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
        info('Quote Code: '.$quote->code.' fn: calculateAllocationStatus sage_reciept_id: '.$paymentSplit->sage_reciept_id.' payment status id: '.$paymentSplit->payment_status_id.' payment_methods_code: '.$payment->payment_methods_code.' Split Payment method '.$paymentSplit->payment_method);

        switch (true) {
            case $paymentSplit && $paymentSplit->sage_reciept_id == null:
                info('Quote Code: '.$quote->code.' Condition: Payment split sage_reciept_id is set to null');

                return PaymentAllocationStatus::NOT_ALLOCATED;
            case in_array($payment->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED, PaymentStatusEnum::NEW]):
                info('Quote Code: '.$quote->code.' Condition: Payment status is PENDING, CREDIT_APPROVED, or NEW');

                return null;
            case $paymentSplit && in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED]):
                info('Quote Code: '.$quote->code.' Condition: Payment split status is PENDING or CREDIT_APPROVED');

                return PaymentAllocationStatus::NOT_ALLOCATED;
            case $collectionAmount <= 0:
                info('Quote Code: '.$quote->code.' Condition: Collection amount is less than or equal to 0');

                return PaymentAllocationStatus::UNPAID;
            case $collectionAmount <= $priceWithVat:
                info('Quote Code: '.$quote->code.' Condition: Collection amount is less than or equal to price with VAT');

                return PaymentAllocationStatus::FULLY_ALLOCATED;
            default:
                info('Quote Code: '.$quote->code.' Condition: Default case, partially allocated');

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

            info('fn: updateQuotePayment payment updated for quote uuid: '.$quoteUuId);
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
            QuoteStatusEnum::POLICY_BOOKING_QUEUED, QuoteStatusEnum::POLICY_BOOKING_FAILED,
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
        SendUpdateStatusLog::updateOrCreate([
            'send_update_log_id' => $sendUpdateLogId,
            'previous_status' => $previousStatus,
            'current_status' => $currentStatus,
        ], [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function checkStatusSUStatusLogs($sendUpdateId, $sendUpdateStatus): bool
    {
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
            info('fn: generateExportLogs export log created');
        } catch (\Exception $e) {
            info('fn: generateExportLogs error: '.$e->getMessage());
        }
    }

    public function synchronizePaymentInformation($quoteObject, $sendUpdatePayment = null, $insuranceProviderId = null)
    {
        info('Quote Code: '.$quoteObject->code.' fn: synchronizePaymentInformation called');
        if (! $sendUpdatePayment) {
            $payment = $quoteObject->payments()->mainLeadPayment()->first();
        } else {
            $payment = $sendUpdatePayment;
        }
        if ($payment) {
            if ($insuranceProviderId) {
                $payment->insurance_provider_id = $insuranceProviderId;
            }
            app(PaymentService::class)->processMasterPayment($payment, $quoteObject);
            app(SplitPaymentService::class)->updateSplitPaymentStatusAndAmount($payment);

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
            return true;
        }
        if (request()->has('quote_type')) {
            $type = request()->quote_type;
        }

        $quote = $this->getQuoteObject($type, $id);
        info('Quote Code: '.$quote->code.' fn: updateQuoteStatus called');
        if (! in_array($quote->quote_status_id, [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::PolicySentToCustomer]) || $quote->policy_issuance_status_id != PolicyIssuanceStatusEnum::PolicyIssued) {
            $isPolicyDetailsFilled = $this->isFilledPolicyDetails($type, $quote);
            info('Quote Code: '.$quote->code.' Is policy details filled : '.$isPolicyDetailsFilled);
            if ($isPolicyDetailsFilled) {
                $quoteDocuments = (new QuoteDocumentService)->getQuoteDocuments($type, $id);
                if (app(QuoteDocumentService::class)->areDocsUploaded($quoteDocuments, $type, $quote)) {
                    $quote->update([
                        'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                        'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
                        'policy_issuance_status_other' => '',
                    ]);
                }
                info('Quote Code: '.$quote->code.' update Quote Status complete for quote_status_id && policy_issuance_status_id');
            }
        }
    }
}
