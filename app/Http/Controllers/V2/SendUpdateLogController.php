<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\PaymentStatusEnum;
use App\Enums\PaymentTooltip;
use App\Enums\quoteBusinessTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveBookingDetailsRequest;
use App\Http\Requests\SavePolicyDetailsRequest;
use App\Http\Requests\SendUpdateCustomerValidationRequest;
use App\Http\Requests\SendUpdateRequest;
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
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
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
        $documentTypes = app(QuoteDocumentService::class)->getQuoteDocumentsForUploadByCategory(SendUpdateLogStatusEnum::SEND_UPDATE);
        $quoteDocuments = $sendUpdateLog->documents;
        $isBookingDetailsVisible = $this->isBookingDetailsVisible($categoryCode, $quoteDocuments);
        $issuanceStatuses = PolicyIssuanceStatusRepository::getColumns(['id', 'text']);
        if (checkPersonalQuotes($quoteType)) {
            $repository = 'App\\Repositories\\'.$quoteType.'QuoteRepository';
            $realQuote = $repository::getBy('uuid', $quote->uuid);
        } else {
            $quoteServiceFile = app(getServiceObject($quoteType));
            $realQuote = $quoteServiceFile->getEntity($quote->uuid);
        }

        $sendUpdateOptions = SendUpdateLogRepository::sendUpdateOptions($quoteTypeId, $sendUpdateLog->category_id, $sendUpdateLog->category->code);

        // booking details section.
        $payments = $this->sendUpdateLogService->getPayments($realQuote->id, $realQuote->uuid, $quoteType);

        $bookingDetails = [];
        if ($payments && is_countable($payments) && count($payments) > 0) {
            // it will also fetch broker_invoice_number and invoice_description, from lead detail page, lead detail broker_invoice_number will
            // always same as ```send update log details``` broker_invoice_number but invoice_description will be overwritten from ```send update log details``` page.
            $bookingDetails = $this->sendUpdateLogService->getInvoiceDescription($sendUpdateLog, $realQuote, $quoteType, $payments[0]['insurance_provider_id']);
            // it will get all invoice_descriptions for booking details
            $paymentInvoices = collect($payments)->pluck('insurer_tax_number');
        }

        if ($sendUpdateLog->is_booking_filled) {
            $bookingDetails = $this->sendUpdateLogService->mergeBookingDetails($bookingDetails, $sendUpdateLog);
        }

        $uploadedDocuments = $this->sendUpdateLogService->getUploadedDocuments($sendUpdateLog);
        // payment related work.
        $this->quoteDocumentService = app(QuoteDocumentService::class);
        $paymentDocumentTypesOptions = $this->quoteDocumentService->paymentDocumentTypesOptions($quoteTypeId);
        $paymentDocumentTypes = $this->quoteDocumentService->getQuoteDocumentsForUpload($quoteTypeId, $paymentDocumentTypesOptions);

        $paymentMethods = app(LookupService::class)->getPaymentMethods();
        $filteredPaymentMethods = $paymentMethods;

        $serviceFile = 'App\\Services\\'.$quoteType.'QuoteService';

        if (! checkPersonalQuotes($quoteType)) {
            $paymentEntityModel = app($serviceFile)->getEntityPlain($realQuote->id);
        }

        $sendUpdatePayments = $this->sendUpdateLogService->getSendUpdatePayments($sendUpdateLog, $quoteType);

        if (in_array($quoteType, [quoteTypeCode::Car, quoteTypeCode::Travel, quoteTypeCode::Health])) {
            $paymentEntityModel->load(['plan']);
        }

        // quote type business only has 2 providers, but as per business lead detail page it's getting providers via Corpline.
        if ($quoteTypeId == QuoteTypeId::Business) {
            $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping(QuoteTypeId::Corpline);
        } else {
            $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($quoteTypeId);
        }
        $linkedQuoteDetails = $this->sendUpdateLogService->linkedQuoteDetails($quoteType, $quote);

        return inertia('SendUpdateLog/Show', [
            'quote' => $quote,
            'quoteType' => $quoteType,
            'sendUpdateLog' => $sendUpdateLog,
            'parentText' => $sendUpdateLog->category->parent->text,
            'sendUpdateOptions' => $sendUpdateOptions,
            'insuranceProviders' => $insuranceProviders,
            'sendUpdateStatusEnum' => SendUpdateLogStatusEnum::asArray(),
            'storageUrl' => storageUrl(),
            'documentTypes' => $documentTypes,
            'quoteDocuments' => array_values($quoteDocuments->toArray()),
            'membersDetail' => CustomerMembersRepository::getBy($quote->id, strtoupper($quoteType)),
            'memberCategories' => app(LookupService::class)->getMemberCategories(),
            'isBookingDetailsVisible' => $isBookingDetailsVisible,
            'realQuote' => $realQuote,
            'isNegativeValue' => $this->sendUpdateLogService->isNegativeValue($sendUpdateLog),
            'bookingDetails' => $bookingDetails,
            'updateBtn' => $this->sendUpdateLogService->getUpdateButtonStatus($sendUpdateLog),
            'paymentInvoices' => $paymentInvoices ?? [],
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
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CI:
                case SendUpdateLogStatusEnum::CIR:
                    $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                        'quote_status_id' => QuoteStatusEnum::CancellationPending,
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
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CIR:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyBooked,
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

    public function savePolicyDetails(SavePolicyDetailsRequest $savePolicyDetailsRequest)
    {
        SendUpdateLogRepository::savePolicyDetails($savePolicyDetailsRequest->validated());

        return redirect()->back();
    }

    public function saveBookingDetails(SaveBookingDetailsRequest $saveBookingDetailsRequest)
    {
        SendUpdateLogRepository::saveBookingDetails($saveBookingDetailsRequest);

        return redirect()->back();
    }

    public function getReversalEntries(Request $request)
    {
        $reversalEntries = app(SendUpdateLogService::class)->getReversalEntries($request->input());

        return response()->json($reversalEntries);
    }

    public function sendUpdateCustomerValidation(SendUpdateCustomerValidationRequest $sendUpdateCustomerValidationRequest)
    {
        $message = app(SendUpdateLogService::class)->getSendToCustomerValidation($sendUpdateCustomerValidationRequest->sendUpdateId);

        return response()->json([
            'message' => $message,
        ]);
    }

    public function sendUpdateToCustomer(UpdateToCustomerRequest $request)
    {
        $data = $request->validated();

        $log = SendUpdateLogRepository::sendUpdateToCustomer($data);

        if (! empty($log->message)) {
            vAbort($log->message);
        }
        $message[] = SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER;

        if ($log && $data['action'] == SendUpdateLogStatusEnum::ACTION_SNBU) {
            $sendUpdateRequest = new SendUpdateRequest();

            $isSendUpdateSuccess = $this->sendUpdate($sendUpdateRequest->merge($data));
            if ($isSendUpdateSuccess->status() == 200) {
                $message[] = SendUpdateLogStatusEnum::UPDATE_BOOKED;
            }
        }

        return response()->json($message);
    }

    public function isBookingDetailsVisible($categoryCode, $quoteDocuments): bool
    {
        $_return = false;
        $documentTypes = $quoteDocuments->pluck('document_type_code')->toArray();

        if (count(array_intersect($documentTypes, [DocumentTypeCode::SEND_UPDATE_POLICY_SCHEDULE, DocumentTypeCode::SEND_UPDATE_POLICY_CERTIFICATE])) > 0) {
            $_return = true;
            $categories = [
                SendUpdateLogStatusEnum::EF,
                SendUpdateLogStatusEnum::CI,
                SendUpdateLogStatusEnum::CIR,
                SendUpdateLogStatusEnum::CPU,
            ];

            if (in_array($categoryCode, $categories)) {
                $requiredDocumentTypes = [DocumentTypeCode::SEND_UPDATE_TAX_INVOICE, DocumentTypeCode::SEND_UPDATE_TAX_INVOICE_RAISED_BUYER];
                $_return = count(array_intersect($documentTypes, $requiredDocumentTypes)) == count($requiredDocumentTypes);
            }
        }

        return $_return;
    }

    public function sendUpdate(SendUpdateRequest $sendUpdateRequest)
    {
        $sendUpdate = SendUpdateLog::find($sendUpdateRequest->sendUpdateId);
        $payment = Payment::where('send_update_log_id', $sendUpdate->id)->first();
        $paymentDetailsUpdate = false;
        $isPaymentFetchedFromMainLead = true;

        if (! isset($sendUpdateRequest->paymentValidated)) {
            // Add insuficient Payment Validations here
            $insufficientPaymentCheck = false;
            if ($payment && in_array($payment->payment_status_id, [PaymentStatusEnum::PARTIALLY_PAID, PaymentStatusEnum::PENDING, PaymentStatusEnum::CREDIT_APPROVED])) {
                $insufficientPaymentCheck = true;
            }

            return response()->json([
                'insufficientPaymentCheck' => $insufficientPaymentCheck,
                'parentPaymentStatus' => $sendUpdate->payments->first()?->payment_status_id ?? null,
            ]);
        }

        info('Book Update Process Start - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdate->uuid);
        $this->sendUpdateLogService = app(SendUpdateLogService::class);

        if ($payment) {
            $isPaymentFetchedFromMainLead = false;
            $paymentDetailsUpdate = $this->sendUpdateLogService->updatePaymentDetails($payment, $sendUpdate);
            info('Book Update - Payment details updated. QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdate->uuid);
        }

        if ($paymentDetailsUpdate || $isPaymentFetchedFromMainLead) {
            // SendUpdateToSagae 3rd parameter: False: Without AP Patch, True: With AP Patch
            // TODO :: This is temporary solution, need to remove third param, this after AP Split patch working fine
            $sageResponse = $this->sendUpdateLogService->sendUpdateToSage($sendUpdateRequest, $sendUpdate, false);
            if ($sageResponse['status'] === false) {

                return response()->json(['message' => $sageResponse['message']], 500);
            }
        }

        // Send Update Data move to main lead page as per Send update Type
        info('Book Update - Moving Send Update impact to Main Lead Page. QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdate->uuid);
        $response = $this->sendUpdateLogService->updatesMoveToLead($sendUpdateRequest, $sendUpdate);

        if ($response['status']) {
            info('Book Update - Process Completed Successfully. QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdate->uuid);

            return response()->json(['message' => $response['message']]);
        }

        logger()->error('Book Update - Something went wrong - Response: '.$response['message'].' - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateUUID: '.$sendUpdate->uuid);

        return response()->json(['message' => $response['message']], 500);
    }

    public function getOptions(Request $request)
    {
        $options = SendUpdateLogRepository::sendUpdateOptions($request->quoteTypeId, $request->parentId, $request->status, $request->businessInsuranceTypeId);

        return response()->json([
            'options' => $options,
        ]);
    }
}
