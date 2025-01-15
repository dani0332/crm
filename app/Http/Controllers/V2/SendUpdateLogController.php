<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReversalEntriesRequest;
use App\Http\Requests\SaveBookingDetailsRequest;
use App\Http\Requests\SavePolicyDetailsRequest;
use App\Http\Requests\SaveProviderDetailsRequest;
use App\Http\Requests\SendUpdateCustomerValidationRequest;
use App\Http\Requests\SendUpdateRequest;
use App\Http\Requests\SendUpdateValidationRequest;
use App\Http\Requests\UpdateToCustomerRequest;
use App\Models\ApplicationStorage;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\SendUpdateLog;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\PersonalQuoteRepository;
use App\Repositories\PolicyIssuanceStatusRepository;
use App\Repositories\QuoteTypeRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\CentralService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\SageApiService;
use App\Services\SendUpdateLogService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SendUpdateLogController extends Controller
{
    private object $sendUpdateLogService;
    private object $quoteDocumentService;

    use GenericQueriesAllLobs;

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $childLeadResponse = [];
            $requestData = $request->all();
            $categoryCode = $requestData['childCategory']['slug'];

            $response = SendUpdateLogRepository::create($requestData);
            if ($response->message) {
                DB::rollBack();

                return redirect()->back()->with('error', $response->message);
            }

            $this->updateQuoteLeadStatus($requestData, 'create');
            if ($categoryCode == SendUpdateLogStatusEnum::CIR) {
                $quoteType = QuoteType::where('id', $requestData['quote_type_id'])->first();
                $quoteModel = $this->getModelObject($quoteType->code);
                $childLeadResponse = app(SendUpdateLogService::class)->createChildLead($quoteModel, $requestData, $quoteType->code);
            }

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            info('Create send update - Failed - Error : '.$exception->getMessage());

            return redirect()->back()->with('error', 'Failed to create send update');
        }

        if (! empty($childLeadResponse)) {
            if ($childLeadResponse['childLeadsCount'] == 0) {
                if (checkPersonalQuotes($childLeadResponse['quote_type_code'])) {
                    return redirect('/personal-quotes/'.strtolower($quoteType->code).'/'.$childLeadResponse['uuid'])
                        ->with('success', $childLeadResponse['ref_id'].' has been created');
                } else {
                    if (isset($childLeadResponse['businessTypeOfInsurance']) && $childLeadResponse['businessTypeOfInsurance'] == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                        return redirect('/medical/amt/'.$childLeadResponse['uuid'])
                            ->with('success', $childLeadResponse['ref_id'].' has been created');
                    } else {
                        return redirect('/quotes/'.strtolower($quoteType->code).'/'.$childLeadResponse['uuid'])
                            ->with('success', $childLeadResponse['ref_id'].' has been created');
                    }
                }
            } else {
                return redirect()->back()->with('error', $childLeadResponse['parent_ref_id'].'-'.$childLeadResponse['childLeadsCount'].' is already created');
            }
        }

        return redirect(route('send-update.show', [
            'uuid' => $response->uuid,
            'quoteUuid' => $requestData['quote_uuid'],
            'refURL' => $requestData['refURL'],
        ]));
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        $sendUpdateLog = SendUpdateLogRepository::getLogByUuid($uuid);
        $isSentOrBooked = app(CentralService::class)->checkStatusSUStatusLogs($sendUpdateLog->id, [SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
            SendUpdateLogStatusEnum::UPDATE_BOOKED]) || in_array($sendUpdateLog->status, [SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER, SendUpdateLogStatusEnum::UPDATE_BOOKED]);

        // we don't need to push this on production, need to remove this before production.
        if (! SendUpdateLogRepository::isCategoryOrOptionAvailable($sendUpdateLog->category_id, $sendUpdateLog->option_id)) {
            return redirect()->back()->with('error', 'Send update log not found');
        }
        $this->sendUpdateLogService = app(SendUpdateLogService::class);
        $isPlanDetailAvailable = $this->sendUpdateLogService->isPlanDetailAvailable($sendUpdateLog); // check Indicative Additional Price section.

        if ($this->sendUpdateLogService->checkSendUpdatePermission($sendUpdateLog->category->code)) {

            return redirect()->back()->with('error', 'You don\'t have permission to this. ');
        }
        $quoteTypeId = $sendUpdateLog->quote_type_id;
        $quoteType = QuoteTypeRepository::where('id', $quoteTypeId)->value('code');

        if ($quoteType == quoteTypeCode::Car) {
            if (in_array($sendUpdateLog->option?->code, [SendUpdateLogStatusEnum::AOCOV, SendUpdateLogStatusEnum::COE, SendUpdateLogStatusEnum::COE_NFI])) {
                $additionalField = $this->sendUpdateLogService->getAdditionalOptionsForCar($sendUpdateLog);
            }
        }

        $quote = PersonalQuoteRepository::getById($sendUpdateLog->personal_quote_id);

        if (in_array($quoteType, [QuoteTypes::CAR, QuoteTypes::HEALTH, QuoteTypes::TRAVEL])) {
            $quote->load('plan.insuranceProvider');
        }

        $categoryCode = $sendUpdateLog->category?->code;
        $optionCode = $sendUpdateLog->option?->code ?? null;
        $documentTypes = $this->sendUpdateLogService->getSendUpdateDocuments($categoryCode, $optionCode);
        $issuanceStatuses = PolicyIssuanceStatusRepository::getColumns(['id', 'text']);
        if (checkPersonalQuotes($quoteType)) {
            $repository = 'App\\Repositories\\'.$quoteType.'QuoteRepository';
            $realQuote = $repository::getBy('uuid', $quote->uuid);
        } else {
            $quoteServiceFile = app(getServiceObject($quoteType));
            $realQuote = $quoteServiceFile->getEntity($quote->uuid);
        }

        $isCommVatNotAppEnabled = $this->sendUpdateLogService->commissionVatNotApplicableEnabled($quoteType, $realQuote?->business_type_of_insurance_id ?? null);

        $parentText = $sendUpdateLog?->option?->code == SendUpdateLogStatusEnum::ATICB ? $realQuote?->transaction_type_text : $sendUpdateLog->category->parent->text;

        // the business_type_of_insurance_id is only on business quotes.
        $sendUpdateOptions = SendUpdateLogRepository::sendUpdateOptions($quoteTypeId, $sendUpdateLog->category_id, $sendUpdateLog->category->code, $realQuote->business_type_of_insurance_id ?? null);

        // booking details section.
        $payments = $this->sendUpdateLogService->getPayments($realQuote->id, $realQuote->uuid, $quoteType);

        if ($categoryCode == SendUpdateLogStatusEnum::CPD) {
            // it will get all invoice_descriptions for booking details
            $paymentInvoices = collect($payments)->whereNotNull('insurer_tax_number')->pluck('insurer_tax_number');
            $sendUpdateLogInvoices = SendUpdateLogRepository::getSendUpdateLogInvoices($quoteTypeId, $realQuote->uuid);
            if (! empty($sendUpdateLogInvoices)) {
                $paymentInvoices = array_merge($paymentInvoices->toArray(), $sendUpdateLogInvoices->toArray());
            }
        }

        $bookingDetails = $this->sendUpdateLogService->getInvoiceDescription($sendUpdateLog, $realQuote, $quoteType);
        $bookingDetails['broker_invoice_number'] = $sendUpdateLog->broker_invoice_number ?? null;
        $uploadedDocuments = $this->sendUpdateLogService->getUploadedDocuments($sendUpdateLog);
        // payment related work.
        $this->quoteDocumentService = app(QuoteDocumentService::class);
        $quoteDocuments = $this->quoteDocumentService->getQuoteDocuments($quoteType, $sendUpdateLog->id, null, true);
        $paymentDocumentTypesOptions = $this->quoteDocumentService->paymentDocumentTypesOptions($quoteTypeId);
        $paymentDocumentTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload($quoteTypeId, $paymentDocumentTypesOptions);

        $paymentMethods = app(LookupService::class)->getPaymentMethods();
        $filteredPaymentMethods = $paymentMethods;

        $serviceFile = 'App\\Services\\'.$quoteType.'QuoteService';

        if (! checkPersonalQuotes($quoteType)) {
            $paymentEntityModel = app($serviceFile)->getEntityPlain($realQuote->id);
        }

        $sendUpdatePayments = $this->sendUpdateLogService->getSendUpdatePayments($sendUpdateLog, $quoteType);

        if (in_array($quoteType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health]) && ! $realQuote?->insly_id) {
            $paymentEntityModel->load(['plan', 'plan.insuranceProvider']);
            $insuranceProviderId = $paymentEntityModel?->plan?->insuranceProvider?->id;
        } else {
            checkPersonalQuotes($quoteType) ? $realQuote->load(['insuranceProvider']) : $paymentEntityModel->load(['insuranceProvider']);
            $insuranceProviderId = $realQuote?->insurance_provider_id ?? $realQuote?->insuranceProvider?->id ?? null;
        }

        // quote type business only has 2 providers, but as per business lead detail page it's getting providers via Corpline.
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($quoteTypeId);
        $linkedQuoteDetails = $this->sendUpdateLogService->linkedQuoteDetails($quoteType, $quote);
        $isEditDisabledForQueuedBooking = $this->sendUpdateLogService->isEditDisabledForQueuedBooking($sendUpdateLog);

        return inertia('SendUpdateLog/Show', [
            'quote' => $quote,
            'quoteLink' => QuoteTypes::getName($quoteTypeId)?->url($quote->uuid),
            'quoteType' => $quoteType,
            'sendUpdateLog' => $sendUpdateLog,
            'parentText' => $parentText,
            'sendUpdateOptions' => $sendUpdateOptions,
            'insuranceProviders' => $insuranceProviders,
            'sendUpdateStatusEnum' => SendUpdateLogStatusEnum::asArray(),
            'storageUrl' => storageUrl(),
            'documentTypes' => $documentTypes,
            'quoteDocuments' => array_values($quoteDocuments->toArray()),
            'membersDetail' => CustomerMembersRepository::getBy($quote->id, strtoupper($quoteType)),
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
            'realQuote' => $realQuote,
            'isNegativeValue' => $this->sendUpdateLogService->isNegativeValue($sendUpdateLog),
            'bookingDetails' => $bookingDetails,
            'updateBtn' => $this->sendUpdateLogService->getUpdateButtonStatus($sendUpdateLog),
            'paymentInvoices' => isset($paymentInvoices) ? array_values(array_unique($paymentInvoices)) : [], // array_values to reset index.
            'uploadedDocuments' => $uploadedDocuments,
            'isPaymentVisible' => $this->sendUpdateLogService->isPaymentVisible($categoryCode, $optionCode),
            'payments' => $sendUpdatePayments,
            'paymentDocumentTypes' => $paymentDocumentTypes,
            'paymentStatusEnum' => PaymentStatusEnum::asArray(),
            'paymentTooltipEnum' => PaymentTooltip::asArray(),
            'paymentMethods' => $filteredPaymentMethods,
            'quoteRequest' => $paymentEntityModel ?? $realQuote,
            'isPolicyDetailsEnabled' => $this->sendUpdateLogService->isPolicyDetailsVisible($categoryCode, $optionCode),
            'linkedQuoteDetails' => $linkedQuoteDetails,
            'additionalField' => $additionalField ?? [],
            'issuanceStatuses' => $issuanceStatuses,
            'isPlanDetailAvailable' => $isPlanDetailAvailable,
            'vatValue' => ApplicationStorage::where('key_name', ApplicationStorageEnums::VAT_VALUE)->first()->value ?? 0,
            'isPaidEditable' => $this->isSplitPaymentFullyPaid($sendUpdatePayments->first()),
            'isEditDisabledForQueuedBooking' => $isEditDisabledForQueuedBooking,
            'insuranceProviderId' => $insuranceProviderId ?? null,
            'isCommVatNotAppEnabled' => $isCommVatNotAppEnabled,
            'isSentOrBooked' => $isSentOrBooked,
            'disableMainBtn' => $this->sendUpdateLogService->disableMainBtn($sendUpdateLog),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();

        $log = SendUpdateLogRepository::updateLog($id, $data);

        if (isset($log->message) && ! empty($log->message)) {
            vAbort($log->message);
        }

        $this->updateQuoteLeadStatus($data, 'update');

        return redirect()->back();
    }

    public function updateQuoteLeadStatus($data, $type)
    {
        $quoteUuid = $data['quote_uuid'];

        $quoteTypeId = $data['quote_type_id'];

        if (! isset($data['childCategory']['slug'])) {
            $selectedType = Lookup::find($data['category_id'])->code;
            $subType['slug'] = ! empty($data['option_id']) ? Lookup::find($data['option_id'])->code : '';
        } else {
            $selectedType = $data['childCategory']['slug'];
            $subType = $data['childCategory']['option'];
        }

        $model = PersonalQuote::class;

        if ($type === 'create') {

            switch ($selectedType) {
                case SendUpdateLogStatusEnum::EF:
                    if ($subType && $subType['slug'] === 'MPC') {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::CancellationPending,
                            'quote_status_date' => now(),
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CI:
                case SendUpdateLogStatusEnum::CIR:
                    $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                        'quote_status_id' => QuoteStatusEnum::CancellationPending,
                        'quote_status_date' => now(),
                    ]);
                    break;
            }
        } else {

            switch ($selectedType) {
                case SendUpdateLogStatusEnum::EF:
                case SendUpdateLogStatusEnum::CI:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyCancelled,
                            'quote_status_date' => now(),
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CIR:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                            'quote_status_date' => now(),
                        ]);

                        // TODO: send it to sage, need to confirm what the sage is.
                    }
                    break;
            }
        }

    }

    public function savePriceDetails(Request $request)
    {
        $data = $request->all();

        SendUpdateLogRepository::updateLogPriceDetails($data);

        return redirect()->back();
    }

    public function savePolicyDetails(SavePolicyDetailsRequest $request)
    {
        SendUpdateLogRepository::savePolicyDetails($request->validated());

        return redirect()->back();
    }

    public function saveBookingDetails(SaveBookingDetailsRequest $request)
    {
        SendUpdateLogRepository::saveBookingDetails($request->validated());

        return redirect()->back();
    }

    public function getReversalEntries(ReversalEntriesRequest $request)
    {
        $reversalEntries = app(SendUpdateLogService::class)->getReversalEntries($request->validated());

        return response()->json($reversalEntries);
    }

    public function sendUpdateCustomerValidation(SendUpdateCustomerValidationRequest $sendUpdateCustomerValidationRequest): \Illuminate\Http\JsonResponse
    {
        $sendUpdateCustomerValidatedRequest = $sendUpdateCustomerValidationRequest->validated();
        $message = app(SendUpdateLogService::class)->getSendToCustomerValidation($sendUpdateCustomerValidatedRequest);
        $response = ['message' => $message];

        if (isset($sendUpdateCustomerValidationRequest->action) && $sendUpdateCustomerValidationRequest->action == SendUpdateLogStatusEnum::ACTION_SNBU) {
            $sendUpdateValidation = $this->sendUpdateValidation(new SendUpdateValidationRequest($sendUpdateCustomerValidatedRequest));

            $sendUpdateValidationResponse = array_merge(['action' => SendUpdateLogStatusEnum::ACTION_SNBU], json_decode($sendUpdateValidation->getContent(), true) ?? []);
            $response = array_merge($response, $sendUpdateValidationResponse);
        }

        return response()->json($response);
    }

    public function sendUpdateToCustomer(UpdateToCustomerRequest $updateToCustomerRequest): \Illuminate\Http\JsonResponse
    {
        $suEmailProcess = SendUpdateLogRepository::sendUpdateToCustomer($updateToCustomerRequest->validated());

        if (isset($suEmailProcess['status']) && $suEmailProcess['status'] == 500) {
            vAbort('Send Update to customer email failed');
        }

        return response()->json($suEmailProcess);
    }

    public function sendUpdateValidation(SendUpdateValidationRequest $sendUpdateValidationRequest)
    {
        $sendUpdateFirstPayment = Payment::where('send_update_log_id', $sendUpdateValidationRequest->sendUpdateId)->first();
        if (! isset($sendUpdateValidationRequest->paymentValidated)) {
            $insufficientPaymentCheck = false;
            if ($sendUpdateFirstPayment && ! $sendUpdateValidationRequest->inslyMigrated && in_array($sendUpdateFirstPayment?->payment_status_id, [
                PaymentStatusEnum::PARTIALLY_PAID,
                PaymentStatusEnum::PENDING,
                PaymentStatusEnum::CREDIT_APPROVED,
            ])) {
                $insufficientPaymentCheck = true;
            }

            return response()->json([
                'insufficientPaymentCheck' => $insufficientPaymentCheck,
                'parentPaymentStatus' => $sendUpdateFirstPayment?->payment_status_id ?? null,
            ]);
        }
    }

    public function sendUpdate(SendUpdateRequest $sendUpdateRequest): \Illuminate\Http\JsonResponse
    {
        $sendUpdateLog = SendUpdateLog::find($sendUpdateRequest->sendUpdateId);
        $endorsementResponse = app(SendUpdateLogService::class)->preparedDataForEndorsement($sendUpdateRequest);

        if (! $endorsementResponse['status'] || ! isset($endorsementResponse['sageRequestPayload'])) {
            $responseMessage = (! isset($endorsementResponse['sageRequestPayload']) && empty($endorsementResponse['message'])) ? 'Something went wrong' : $endorsementResponse['message'];

            return response()->json(['message' => $responseMessage], 500);
        }

        info('fn:sendUpdate - Calling updateSageProcessForDispatching function through sendUpdate - SendUpdateCode: '.$sendUpdateLog->code);
        app(SendUpdateLogService::class)->updateSageProcessForDispatching($sendUpdateRequest->toArray(), $sendUpdateLog, $endorsementResponse['sageRequestPayload']);

        (new SageApiService)->scheduleSageProcesses($endorsementResponse['sageRequestPayload']->insurerID);
        info('fn:sendUpdate - fn:scheduleSageProcesses triggered for Insurer - '.$endorsementResponse['sageRequestPayload']->insurerID.' - SendUpdateCode: '.$sendUpdateLog->code);

        return response()->json(['message' => $endorsementResponse['message']], 200);
    }

    public function getOptions(Request $request)
    {
        $options = SendUpdateLogRepository::sendUpdateOptions($request->quoteTypeId, $request->parentId, $request->status, $request->businessInsuranceTypeId);

        return response()->json([
            'options' => $options,
        ]);
    }

    public function saveProviderDetails(SaveProviderDetailsRequest $request)
    {
        SendUpdateLogRepository::saveProviderDetails($request->validated());

        return redirect()->back();
    }
}
