<?php

namespace App\Services;

use Log;
use Carbon\Carbon;
use App\Facades\Ken;
use App\Facades\Capi;
use App\Models\Payment;
use App\Models\CarQuote;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeId;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use App\Enums\quoteTypeCode;
use App\Enums\LeadSourceEnum;
use App\Models\PersonalQuote;
use App\Enums\QuoteStatusEnum;
use App\Models\QuoteStatusLog;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Models\ApplicationStorage;
use Illuminate\Support\Facades\DB;
use App\Models\PersonalQuoteDetail;
use App\Traits\GenericQueriesAllLobs;
use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentAllocationStatus;
use App\Repositories\PersonalQuoteRepository;

class CentralService
{
    use GenericQueriesAllLobs;

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

                $response = in_array(ucfirst($lob), newUi()) ?
                    ((method_exists($repository, 'fetchCreateDuplicate') && ! checkPersonalQuotes(ucfirst($lob))) ? $repository::createDuplicate($dataArr) : PersonalQuoteRepository::createDuplicate($dataArr, ucfirst($lob))) :
                    Capi::request('/api/v1-save-'.strtolower($lob).'-quote', 'post', $dataArr);

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
        Log::info('Leads ids to assign: '.json_encode($leadsIds));

        if (str_starts_with($leadsIds, ',')) {
            $leadsIds = substr($leadsIds, 1);
        }

        $leadsIds = array_map('intval', explode(',', $leadsIds));
        $model = (in_array(ucfirst($request->modelType), $personalQuotes) && in_array(ucfirst($request->modelType), newUi())) ?
            ['parent' => PersonalQuote::class, 'child' => PersonalQuoteDetail::class] :
            ['parent' => (ucfirst($request->modelType).'Quote'), 'child' => (ucfirst($request->modelType).'QuoteRequestDetail')];

        if (! class_exists($model['parent'])) {
            vAbort('Something went wrong');
        }

        return DB::transaction(function () use ($leadsIds, $model, $request, $personalQuotes) {
            foreach ($leadsIds as $leadId) {

                $getQuoteLead = $model['parent']::findOrfail($leadId);
                $getQuoteLead->advisor_id = (int) $request->assigned_advisor_id;
                $getQuoteLead->save();

                $parentFieldName = (in_array(ucfirst($request->modelType), $personalQuotes) && in_array(ucfirst($request->modelType), newUi())) ?
                    'personal_quote_id' : strtolower($request->modelType).'_quote_request_id';

                $model['child']::updateOrCreate(
                    [$parentFieldName => $getQuoteLead->id],
                    ['advisor_assigned_by_id' => auth()->user()->id, 'advisor_assigned_date' => Carbon::now()]
                );
            }
        });
    }

    public function loadAvailablePlans($type, $id)
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
                                $plan->plan_type = HealthPlanTypeEnum::typeName($plan->planTypeId)?->label();
                            }
                        }
                    }
                }

                return $listQuotePlans;
            default:
                return [];
        }
    }

    public function updateQuotePayment($quote, $priceWithVat)
    {
        info('fn: updateQuotePayment called');

        if ($quote->payments()->count() > 0) {
            info('fn: updateQuotePayment payment found to be updated for quote uuid: '.$quote->uuid);

            $payment = $quote->payments->first();

            $paymentData = ['total_price' => $priceWithVat];

            if ($priceWithVat > $payment->total_price && in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED])) {
                $paymentData['payment_status_id'] = PaymentStatusEnum::PARTIALLY_PAID;
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

            $this->updateQuotePayment($quote, $data->price_with_vat);

            return true;

        });
    }

    public function updateSelectedPlan($quoteType, $uuid, $data)
    {
        $response = [];
        $requestData = $data;

        //switch for quote type
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
                info('car plan update response: '.json_encode($response));

                // if (isset($response['planProcessValue']['totalPremium'])) {
                //     $quote = CarQuote::where('uuid', $uuid)->first();
                //     $this->updateQuotePayment($quote, $response['planProcessValue']['totalPremium']);
                // }
                break;
            case QuoteTypes::TRAVEL->value:
                $endpoint = '/process-travel-quote-plan';
                $data = [
                    'quoteTypeId' => QuoteTypeId::Car,
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
                info('travel plan update response: '.json_encode($response));

                // if (isset($response['planProcessValue'])) {
                //     $quote = TravelQuote::where('uuid', $uuid)->first();
                //     $this->updateQuotePayment($quote, collect($response['planProcessValue'])->sum('totalPremium'));
                // }
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
                ];

                $response = Capi::request($endpoint, 'post', $data);
                info('health plan update response: '.json_encode($response));
                // if (isset($response->totalPremium)) {
                //     $quote = HealthQuote::where('uuid', $uuid)->first();
                //     $this->updateQuotePayment($quote, $response->totalPremium);
                // }
                break;
        }

        return $response;
    }
    //check if aml cleared from log
    public function amlClearedFromLog($quoteId, $quoteType)
    {
        $quoteType = strtolower($quoteType);
        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId($quoteType);

        $isAmlClearedForPayment = false;
        $quoteStatusLog = QuoteStatusLog::where('quote_request_id', $quoteId)
            ->where('quote_type_id', $quoteTypeId)
            ->where(function ($q) {
                $q->where('current_quote_status_id', QuoteStatusEnum::AMLScreeningCleared);
                $q->orWhere('previous_quote_status_id', QuoteStatusEnum::AMLScreeningCleared);
            })->orderBy('id', 'desc')->first();
        if ($quoteStatusLog) {
            $amlScreenFailed = QuoteStatusLog::where('quote_request_id', $quoteId)
                ->where('quote_type_id', $quoteTypeId)
                ->where('current_quote_status_id', QuoteStatusEnum::AMLScreeningFailed)
                ->where('id', '>', $quoteStatusLog->id)
                ->first();
            if (! $amlScreenFailed) {
                $isAmlClearedForPayment = true;
            }
        }

        return $isAmlClearedForPayment;
    }

    // This method is used to update payment allocation status when lead status is updated
    public function updatePaymentAllocation($modelType, $quote_uuid)
    {
        $quote = $this->getQuoteObject($modelType, $quote_uuid);
        if ($quote->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            $payment = Payment::where('code', $quote->code)->with('paymentSplits')->first();
            $this->straightforwardPayments($payment, $payment->paymentSplits, $quote);
        }
    }

    public function straightforwardPayments($payment, $paymentSplits, $quote)
    {
        if ($payment) {
            $this->updatePaymentAllocationStatus($payment, $quote);
            if (in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SEMI_ANNUAL, PaymentFrequency::QUARTERLY, PaymentFrequency::MONTHLY, PaymentFrequency::CUSTOM])) {
                $paymentSplit = $paymentSplits->first();
                $this->firstSplitAllocationStatus($payment, $paymentSplit, $quote);
            }

            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                $this->updatePaymentSplitAllocationStatus($paymentSplits, $quote);
            }
        }
    }

    private function updatePaymentAllocationStatus($payment, $quote)
    {
        $payment->payment_allocation_status = $this->calculateAllocationStatus($payment, $quote);
        $payment->save();
    }

    private function calculateAllocationStatus($payment, $quote, $paymentSplit = null)
    {
        $collectionAmount = $paymentSplit ? $paymentSplit->collection_amount : $payment->captured_amount;
        $priceWithVat = $quote->price_with_vat;

        switch (true) {
            case in_array($payment->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED, PaymentStatusEnum::NEW]):
                return null;
            case ($payment->frequency == PaymentFrequency::UPFRONT && $paymentSplit != null):
                return $payment->payment_allocation_status;
            case $paymentSplit && in_array($paymentSplit->payment_status_id, [PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED]):
                return PaymentAllocationStatus::NOT_ALLOCATED;
            case $collectionAmount <= 0:
                return PaymentAllocationStatus::UNPAID;
            case $collectionAmount <= $priceWithVat:
                return PaymentAllocationStatus::FULLY_ALLOCATED;
            default:
                return PaymentAllocationStatus::PARTIALLY_ALLOCATED;
        }
    }

    private function firstSplitAllocationStatus($payment, $paymentSplit, $quote)
    {
        $paymentSplit->payment_allocation_status = $this->calculateAllocationStatus($payment, $quote, $paymentSplit);
        $paymentSplit->save();
    }
    
    private function updatePaymentSplitAllocationStatus($paymentSplits, $quote)
    {
        $collectedAmount = 0;
        foreach ($paymentSplits as $paymentSplit) {
            $collectedAmount += $paymentSplit->collection_amount;
            $paymentSplit->payment_allocation_status = $this->calculateSplitAllocationStatusWithCollectedAmount($paymentSplit, $quote, $collectedAmount);
            $paymentSplit->save();
        }
    }

    private function calculateSplitAllocationStatusWithCollectedAmount($paymentSplit, $quote, $collectedAmount)
    {
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
    
}
