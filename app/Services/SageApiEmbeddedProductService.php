<?php

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypes;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Factories\SagePayloadFactory;
use App\Models\EmbeddedTransaction;
use App\Models\InsuranceProvider;
use App\Models\InsurerRequestResponse;
use App\Models\Payment;
use App\Models\QuoteTag;
use App\Models\SageProcess;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use stdClass;

class SageApiEmbeddedProductService
{
    use GenericQueriesAllLobs;
    use SageLoggable;
    use TeamHierarchyTrait;

    const CLASSNAME = 'sageApiEmbeddedProductService';

    protected $sageApiService;

    public function __construct()
    {
        $this->sageApiService = new SageApiService;
    }

    public function scheduleBookingOfEmbeddedProduct($request)
    {
        $user = auth()->user();
        $quoteTypeId = QuoteTypes::getIdFromValue($request['modelType']);
        $quote = $this->getQuoteObjectBy($request['modelType'], $request['quoteId']);

        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();
        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = Payment::where([
                'paymentable_id' => $quote->id,
                'paymentable_type' => $quote->getMorphClass(),
            ])->mainLeadPayment()->with('paymentSplits')->first();
        }
        $paymentSplits = $payment->paymentSplits;

        $epTransaction = EmbeddedTransaction::where('id', $request['epTransactionId'])->first();
        $insuranceProvider = InsuranceProvider::find($request['insuranceProviderId']);
        $isSukoonInsuranceProvider = in_array($insuranceProvider?->code, $this->sageApiService->allowedProviderForSageEPBooking());
        if (! $isSukoonInsuranceProvider) {
            return ['status' => false, 'message' => 'Sage booking cannot be scheduled because current insurer is '.$insuranceProvider?->text];
        }

        $sageRequest = app(SagePayloadFactory::class)->sagePayLoad($request['modelType'], $payment, $quote, $paymentSplits);
        $sageRequest->userId = $user?->id;
        $sageRequest->insurerID = $request['insuranceProviderId'];
        $sageRequest->sageProcessRequestType = SageEnum::SAGE_PROCESS_BOOK_EMBEDDED_PRODUCT_REQUEST;
        $sageRequest->epTransactionId = $request['epTransactionId'];
        $sageRequest->epInsuranceProviderId = $request['insuranceProviderId'];
        $sageRequest->quoteType = $request['modelType'];
        $sageRequest->quoteId = $request['quoteId'];
        $sageRequest->quoteTypeId = $quoteTypeId;
        $sageRequest->quoteCode = $quote->code;

        $data = ['id' => $quote->id, 'quoteTypeId' => $quoteTypeId];
        $sageRequest->customerId = $this->sageApiService->verifySageCustomer($quote->customer_id, $data, $quote, 15);

        $sageProcessData = [
            'user_id' => $sageRequest->userId,
            'insurance_provider_id' => $sageRequest->insurerID,
            'request' => json_encode([
                'sagePayload' => $sageRequest,
                'requestPayload' => $request,
            ]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ];

        $sageProcess = SageProcess::where([
            'model_type' => $epTransaction::class,
            'model_id' => $epTransaction->id,
        ])->first();

        if ($sageProcess) {
            if ($sageProcess->status == SageEnum::SAGE_PROCESS_FAILED_STATUS) {
                $sageProcess->update($sageProcessData);
                $this->sageApiService->scheduleSageProcesses($sageRequest->insurerID);
                $this->updateAndLogEPBookingStatus($epTransaction, SageEmbeddedProductEnum::BOOKING_QUEUED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return ['status' => true, 'message' => 'Embedded Product Booking Process is scheduled for EP Code: '.$epTransaction->code];
            }

            return ['status' => false, 'message' => 'Embedded Product Booking Process is already scheduled/booked for EP Code: '.$epTransaction->code];
        } else {
            $sageProcessData['model_type'] = $epTransaction::class;
            $sageProcessData['model_id'] = $epTransaction->id;
            SageProcess::create($sageProcessData);
            $this->sageApiService->scheduleSageProcesses($sageRequest->insurerID);
            $this->updateAndLogEPBookingStatus($epTransaction, SageEmbeddedProductEnum::BOOKING_QUEUED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            return ['status' => true, 'message' => 'Embedded Product Booking Process is scheduled for EP Code: '.$epTransaction->code];
        }
    }

    public function bookReversalOfEmbeddedProductOnSage($sageRequestDataArray)
    {
        [$quote ,$sendUpdateLog, $sageRequest, $sukoonMedXTransaction] = $sageRequestDataArray;

        LoggerService::startQuoteLogging($sukoonMedXTransaction, LoggerFeatureEnum::SAGE_EP_BOOKING_REVERSAL);
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - SendUpdate Code: '.$sendUpdateLog->code.' - Embedded Product Booking Reversal started for EP Code: '.$sukoonMedXTransaction->code);

        $viewQuotePolicyApiLog = InsurerRequestResponse::where([
            'quote_uuid' => $quote->uuid, 'status' => 'passed', 'execution_method' => 'viewQuotePolicy',  'call_type' => 'EmbeddedProduct',
        ])->latest()->first();

        $createARInvoiceForEPLog = $sukoonMedXTransaction?->sageApiLogs?->where('sage_request_type', SageEnum::EP_SRT_CREATE_AR_PREM_COMM_INV)->first();
        $createEPARPayload = json_decode($createARInvoiceForEPLog->sage_payload, true);
        $sageRequest->customerId = $createEPARPayload['Invoices'][0]['CustomerNumber'];

        $sageRequestEmbeddedProduct = self::createEmbeddedProductPayload($sukoonMedXTransaction, $viewQuotePolicyApiLog);
        $quoteTypeId = $sageRequest->quoteTypeId;

        $sageLogArray = $sendUpdateLog->sageApiLogs->keyBy('step')->toArray();
        // Create AR Commission and Premium Invoice
        $createARInvoicePremAndComm = $this->createARInvoicePremAndCommReversal([$sendUpdateLog, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray], true);
        if (! $createARInvoicePremAndComm['status']) {
            $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            return $createARInvoicePremAndComm;
        }

        // Create AP Premium Invoice
        $createAPInvoicePrem = $this->createAPPremInvoiceReversal([$sendUpdateLog, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray], true);
        if (! $createAPInvoicePrem['status']) {
            $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            return $createAPInvoicePrem;
        }

        $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_CANCELLED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - SendUpdate Code: '.$sendUpdateLog->code.' - Reversal of Embedded Product Booking Process Completed for EP Code: '.$sukoonMedXTransaction->code);

        return ['status' => true, 'message' => 'Reversal of Embedded Product is Booked for SendUpdate Code : '.$sendUpdateLog->code.' and EP Code: '.$sukoonMedXTransaction->code];
    }

    public function bookEmbeddedProductOnSage($sageRequestDataArray)
    {
        [$quote ,$sageRequest, $sukoonMedXTransaction] = $sageRequestDataArray;
        $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_QUEUED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

        LoggerService::startQuoteLogging($sukoonMedXTransaction, LoggerFeatureEnum::SAGE_EP_BOOKING);
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - Quote Code: '.$quote->code.' - Embedded Product Booking started for: '.$sukoonMedXTransaction->code);

        $viewQuotePolicyApiLog = InsurerRequestResponse::where([
            'quote_uuid' => $quote->uuid, 'status' => 'passed', 'execution_method' => 'viewQuotePolicy',  'call_type' => 'EmbeddedProduct',
        ])->latest()->first();

        $sageRequestEmbeddedProduct = self::createEmbeddedProductPayload($sukoonMedXTransaction, $viewQuotePolicyApiLog);
        $quoteTypeId = $sageRequest->quoteTypeId;

        $sageLogArray = $sukoonMedXTransaction->sageApiLogs->keyBy('step')->toArray();

        $isEmbeddedProductBookedOnSage = QuoteTag::where([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::EMBEDDED_PRODUCT_BOOKED_ON_SAGE,
            'value' => 1,
        ])->first();

        if (! $isEmbeddedProductBookedOnSage) {
            // Execute AR Prepayment Receipt Post Call
            $arReceiptCreationResponse = $this->createARPrepaymentReceipt([$quote, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $arReceiptCreationResponse['status']) {
                $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $arReceiptCreationResponse;
            }
            $sageRequestEmbeddedProduct->epSageReceiptId = $arReceiptCreationResponse['documentNumber'];

            // Execute AP Prepayment Receipt Post Call
            $apReceiptCreationResponse = $this->createAPPrepaymentReceipt([$quote, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $apReceiptCreationResponse['status']) {
                $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $apReceiptCreationResponse;
            }
            $sageRequestEmbeddedProduct->epSageAPReceiptId = $apReceiptCreationResponse['documentNumber'];

            // Create AR Commission and Premium Invoice
            $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$quote, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $createARInvoicePremAndComm['status']) {
                $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $createARInvoicePremAndComm;
            }

            // Create AP Premium Invoice
            $createAPInvoicePrem = $this->createAPPremInvoice([$quote, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $createAPInvoicePrem['status']) {
                $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $createAPInvoicePrem;
            }

            // Apply Prepayments AR Invoice
            $applyPaymentARInvoices = $this->applyPaymentARInvoices([$quote, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $applyPaymentARInvoices['status']) {
                $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $applyPaymentARInvoices;
            }

            // Apply Prepayments AP Invoice
            $applyPaymentAPInvoices = $this->applyPaymentAPInvoices([$quote, $sukoonMedXTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $applyPaymentAPInvoices['status']) {
                $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $applyPaymentAPInvoices;
            }

            $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_COMPLETED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            QuoteTag::create([
                'quote_type_id' => $quoteTypeId,
                'quote_uuid' => $quote->uuid,
                'name' => QuoteTagEnums::EMBEDDED_PRODUCT_BOOKED_ON_SAGE,
                'value' => 1,
            ]);

            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - Embedded Product Booked for: '.$quote->code);
        } else {
            $this->updateAndLogEPBookingStatus($sukoonMedXTransaction, SageEmbeddedProductEnum::BOOKING_COMPLETED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - Embedded Product Booked Already for: '.$quote->code);
        }

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - Embedded Product Booking Process Completed for: '.$sukoonMedXTransaction->code);

        return ['status' => true, 'message' => 'Embedded Product is Booked for EP Code : '.$sukoonMedXTransaction->code];
    }

    private function createARPrepaymentReceipt($sageRequestDataArray): array
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'documentNumber' => null];
        [$quote, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct , $sageLogArray] = $sageRequestDataArray;
        $quoteTypeId = $sageRequest->quoteTypeId;
        $isAlreadyPosted = false;

        $totalSteps = 3;
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code);

        $payLoadOptions = self::createARPaymentReceiptsPayload($sageRequest, $sageRequestEmbeddedProduct);
        $currentStep = 1;
        $isLiveApiCallStep1 = true;
        if (isset($sageLogArray[1]) && $sageLogArray[1]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep1 = false;
            $sageResponse = json_decode($sageLogArray[1]['response'], true);
        } else {

            $createPremiumPrepaymentResponse = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($createPremiumPrepaymentResponse, true);
        }

        if (isset($sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'])) {
            $response['documentNumber'] = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            if (! $sukoonMedXEPTransaction->sage_ar_payment_receipt_id) {
                $sukoonMedXEPTransaction->update(['sage_ar_payment_receipt_id' => $response['documentNumber']]);
            }
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments: Created AR Prepayment Receipts batch '.$sageResponse['BatchNumber']);
            if ($isLiveApiCallStep1) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
            $currentStep = 2;
            $isLiveApiCallStep2 = true;
            $readyToPostReceiptAr = self::readyToPostARPaymentReceiptPayload($sageResponse['BatchNumber']);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to post AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' Error: '.$readyToPostArray['error']['message']['value']);

                    $aRReceiptBatch = $this->sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments: Status of AR Prepayment Receipts batch: '.$aRReceiptBatch);
                    $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                    if (isset($aRReceiptBatch['BatchStatus']) && $aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aRReceiptBatch['BatchStatus'])) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to get Prepayment Batch Status for AR Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$sukoonMedXEPTransaction->code.' Failed to get Prepayment Batch Status';

                        return $response;
                    } else {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to post AR Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$sukoonMedXEPTransaction->code.' Error while making ready to post to sage';

                        return $response;
                    }
                } else {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            } else {
                if ($isLiveApiCallStep2) {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' post prepayment for EP Code : '.$sukoonMedXEPTransaction->code, ['BatchNumber' => $sageResponse['BatchNumber']]);
            $isLiveApiCallStep3 = true;
            $currentStep = 3;
            $aRPostReceipts = self::postARPaymentReceiptPayload($sageResponse['BatchNumber']);
            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[3]['response'], true);
            } else {
                $postedResponse = $this->sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($postedResponse, true);
            }

            if ($isAlreadyPosted && isset($aRPostReceipts)) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            } else {
                if (isset($postedResponse['error'])) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to post AR Receipts for batch '.$sageResponse['BatchNumber'], extra: [
                        'error' => $postedResponse['error'],
                    ]);
                    $response['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' :  Error while posting to sage';
                    $sageErrorMessage = $postedResponse['error']['message']['value'] ?? $postedResponse['error'] ?? null;
                    if ($this->sageApiService->sageHasProcessingConflict($sageErrorMessage)) {
                        $response['message'] = SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE;
                    }
                    $this->logSageApiCall($aRPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);

                    return $response;
                } else {
                    if ($isLiveApiCallStep3) {
                        $this->logSageApiCall($aRPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }

            }

            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments: Successfully created receipt');
            $response['status'] = true;
            $response['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' : Prepayment created';
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Document number not generated from Sage', extra: [
                'sageResponse' => $sageResponse,
            ]);
            $this->logSageApiCall($payLoadOptions, $sageResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
            $response['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' : Document number not generated from sage - Ref:'.$quote->code;
        }

        return $response;
    }

    private function createAPPrepaymentReceipt($sageRequestDataArray): array
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'documentNumber' => null];
        [$quote, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct , $sageLogArray] = $sageRequestDataArray;
        $quoteTypeId = $sageRequest->quoteTypeId;
        $isAlreadyPosted = false;

        $totalSteps = 6;
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code);

        $payLoadOptions = self::createAPPaymentReceiptsPayload($sageRequest, $sageRequestEmbeddedProduct);
        $currentStep = 4;
        $isLiveApiCallStep4 = true;
        if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep4 = false;
            $sageResponse = json_decode($sageLogArray[4]['response'], true);
        } else {
            $createAPPrepaymentResponse = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($createAPPrepaymentResponse, true);
        }

        if (isset($sageResponse['PaymentsAdjustments'][0]['DocumentNumber'])) {
            $response['documentNumber'] = $sageResponse['PaymentsAdjustments'][0]['DocumentNumber'];
            if (! $sukoonMedXEPTransaction->sage_ap_payment_receipt_id) {
                $sukoonMedXEPTransaction->update(['sage_ap_payment_receipt_id' => $response['documentNumber']]);
            }
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments: Created AP Prepayment Receipts batch '.$sageResponse['BatchNumber']);
            if ($isLiveApiCallStep4) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
            $currentStep = 5;
            $isLiveApiCallStep5 = true;
            $readyToPostReceiptAP = self::readyToPostAPPaymentReceiptPayload($sageResponse['BatchNumber']);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAP['endPoint'], $readyToPostReceiptAP['payload'], 'PATCH');

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to post AP Prepayment Receipts batch '.$sageResponse['BatchNumber'].' Error: '.$readyToPostArray['error']['message']['value']);

                    $aPReceiptBatch = $this->sageApiService->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments: Status of AP Prepayment Receipts batch: '.$aPReceiptBatch);
                    $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                    if (isset($aPReceiptBatch['BatchStatus']) && $aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aPReceiptBatch['BatchStatus'])) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to get Prepayment Batch Status for AP Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$sukoonMedXEPTransaction->code.' Failed to get AP Prepayment Batch Status';

                        return $response;
                    } else {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to post AP Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$sukoonMedXEPTransaction->code.' Error while making AP ready to post to sage';

                        return $response;
                    }
                } else {
                    $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            } else {
                if ($isLiveApiCallStep5) {
                    $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' post AP prepayment for EP Code : '.$sukoonMedXEPTransaction->code, ['BatchNumber' => $sageResponse['BatchNumber']]);
            $isLiveApiCallStep6 = true;
            $currentStep = 6;
            $aPPostReceipts = self::postAPPaymentReceiptPayload($sageResponse['BatchNumber']);
            if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep6 = false;
                $postedResponse = json_decode($sageLogArray[6]['response'], true);
            } else {
                $postedResponse = $this->sageApiService->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                $postedResponse = json_decode($postedResponse, true);
            }

            if ($isAlreadyPosted && isset($aPPostReceipts)) {
                $this->logSageApiCall($aPPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            } else {
                if (isset($postedResponse['error'])) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Failed to post AP Receipts for batch '.$sageResponse['BatchNumber']);
                    $response['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' :  Error while posting AP to sage';
                    $this->logSageApiCall($aPPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);

                    return $response;
                } else {
                    if ($isLiveApiCallStep6) {
                        $this->logSageApiCall($aPPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }

            }

            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments: Successfully created AP receipt');
            $response['status'] = true;
            $response['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' : AP Prepayment created';
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' SAGE API Payments Error: Document number not generated from Sage');
            $this->logSageApiCall($payLoadOptions, $sageResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
            $response['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' : Document number not generated from sage - Ref:'.$quote->code;
        }

        return $response;
    }

    private function createARInvoicePremAndComm($sageRequestDataArray)
    {
        [$quote, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;

        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 6;
        $stepsMapping = ['step_1' => 4, 'step_2' => 5, 'step_3' => 6];

        $isLiveApiCallStep1 = true;
        $payLoadOptions = self::createARPremAndComInvoicePayload($sageRequest, $sageRequestEmbeddedProduct);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' createARInvoicePremAndComm  Sent Already for '.$quote->code);
            $isLiveApiCallStep1 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send createARInvoicePremAndComm  for '.$quote->code);
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' :  Batch Number - '.$sageResponse['BatchNumber'].' for createARInvoicePremAndComm');
            if ($isLiveApiCallStep1) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $sukoonMedXEPTransaction, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
            $isLiveApiCallStep2 = true;
            $readyToPostInvoiceAr = self::readyToPostARPremAndCommInvoicePayload($sageResponse['BatchNumber']);
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  readyToPostARPremAndCommInvoice  Sent Already for '.$quote->code);
                $isLiveApiCallStep2 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send readyToPostARPremAndCommInvoice  for '.$quote->code);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Error while making Ar invoice & prem ready to post to sage';
                $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            }
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' : '.$quote->code.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep3 = true;
            $aRPostInvoices = self::postARPremAndCommInvoicePayload($sageResponse['BatchNumber']);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  aRPostInvoices  Sent Already for '.$quote->code);
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Check status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$quote->code);
                    $arInvoiceBatch = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$quote->code, extra: $arInvoiceBatch);
                    if (! isset($arInvoiceBatch['BatchStatus'])) {
                        $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : Upfront - AR Invoice batch status key not defined';
                        $returnMessage['message'] = $message;
                        $returnMessage['error'] = $message;

                        return $returnMessage;
                    }
                    if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' : AR invoice batch '.$sageResponse['BatchNumber'].' already posted for '.$quote->code);
                        $postedResponse = $aRPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send aRPostInvoices  for '.$quote->code);
                    $resp = $this->sageApiService->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Error while making Ar invoice & prem Posted to sage';
                $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            }
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' : '.$quote->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $sukoonMedXEPTransaction, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        } else {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Ar invoice & prem failed from sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : createARInvoicePremAndComm  failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Premium and Commission invoice created on sage for EP Code '.$sukoonMedXEPTransaction->code;

        return $returnMessage;

    }

    private function createAPPremInvoice($sageRequestDataArray)
    {
        [$quote, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 9;
        $stepsMapping = ['step_1' => 7, 'step_2' => 8, 'step_3' => 9];

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  Start of Upfront createAPInvoicePrem for : '.$quote->code.' ');

        $isLiveApiCallStep5 = true;
        $createAPInvoicePrem = self::createAPPremInvoicePayload($sageRequest, $sageRequestEmbeddedProduct);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  createAPInvoicePrem  Sent Already for '.$quote->code);
            $isLiveApiCallStep5 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send createAPInvoicePrem  for '.$quote->code);
            $resp = $this->sageApiService->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : readyToPostInvoiceAr - '.$postedResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep5) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $sukoonMedXEPTransaction, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep6 = true;
            $readyToPostInvoiceAP = self::readyToPostAPPremInvoicePayload($postedResponse['BatchNumber']);
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  readyToPostInvoiceAP  Sent Already for '.$quote->code);
                $isLiveApiCallStep6 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send readyToPostInvoiceAP  for '.$quote->code);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Error while making AP invoice ready to post to sage';
                $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep6) {
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            $isLiveApiCallStep7 = true;
            $aPPostInvoices = self::postAPPremInvoicePayload($postedResponse['BatchNumber']);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  aPPostInvoices  Sent Already for '.$quote->code);
                $isLiveApiCallStep7 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Check status of  AP invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                    $aPInvoiceBatch = $this->sageApiService->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : Status of  AP invoice batch('.$postedResponse['BatchNumber'].'): ', extra: $aPInvoiceBatch);
                    if (! isset($aPInvoiceBatch['BatchStatus'])) {
                        $message = 'Upfront - AP Invoice batch status key not defined';
                        $returnMessage['message'] = $message;
                        $returnMessage['error'] = $message;

                        return $returnMessage;
                    }

                    if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : AP invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                        $postedResponse = $aPPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send aPPostInvoices  for '.$quote->code);
                    $resp = $this->sageApiService->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making AP invoices Posted to sage';
                $message = 'aPPostInvoices failed';

                return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - SAGE API: '.$quote->code.' - aPPostInvoices completed successfully');
                if ($isLiveApiCallStep7) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $sukoonMedXEPTransaction, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }
        } else {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Ap invoice prem failed from sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : createAPInvoicePrem  failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - End of Upfront createAPInvoicePrem for: '.$quote->code);

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Premium invoice created on sage for EP Code '.$sukoonMedXEPTransaction->code;

        return $returnMessage;
    }

    private function createARInvoicePremAndCommReversal($sageRequestDataArray, $isReversal = false)
    {
        [$sendUpdateLog, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;

        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 27;
        $stepsMapping = ['step_1' => 25, 'step_2' => 26, 'step_3' => 27];

        $isLiveApiCallStep1 = true;
        $payLoadOptions = self::createARPremAndComInvoicePayload($sageRequest, $sageRequestEmbeddedProduct, $isReversal);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' createARInvoicePremAndComm  Sent Already for '.$sendUpdateLog->code);
            $isLiveApiCallStep1 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send createARInvoicePremAndComm  for '.$sendUpdateLog->code);
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' Batch Number - '.$sageResponse['BatchNumber'].' for createARInvoicePremAndComm');
            if ($isLiveApiCallStep1) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
            $isLiveApiCallStep2 = true;
            $readyToPostInvoiceAr = self::readyToPostARPremAndCommInvoicePayload($sageResponse['BatchNumber'], $isReversal);
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  readyToPostARPremAndCommInvoice  Sent Already for '.$sendUpdateLog->code);
                $isLiveApiCallStep2 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send readyToPostARPremAndCommInvoice  for '.$sendUpdateLog->code);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Error while making Ar invoice & prem ready to post to sage';
                $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            }
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep3 = true;
            $aRPostInvoices = self::postARPremAndCommInvoicePayload($sageResponse['BatchNumber'], $isReversal);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  aRPostInvoices  Sent Already for '.$sendUpdateLog->code);
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Check status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$sendUpdateLog->code);
                    $arInvoiceBatch = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$sendUpdateLog->code, extra: $arInvoiceBatch);
                    if (! isset($arInvoiceBatch['BatchStatus'])) {
                        $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : Upfront - AR Invoice batch status key not defined';
                        $returnMessage['message'] = $message;
                        $returnMessage['error'] = $message;

                        return $returnMessage;
                    }
                    if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' : AR invoice batch '.$sageResponse['BatchNumber'].' already posted for '.$sendUpdateLog->code);
                        $postedResponse = $aRPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send aRPostInvoices  for '.$sendUpdateLog->code);
                    $resp = $this->sageApiService->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Error while making Ar invoice & prem Posted to sage';
                $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            }
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API : SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        } else {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' : Ar invoice & prem failed from sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.' : createARInvoicePremAndComm  failed';

            return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Reversal of AR Premium and Commission invoice created on sage for SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code;

        return $returnMessage;

    }

    private function createAPPremInvoiceReversal($sageRequestDataArray, $isReversal = false)
    {
        [$sendUpdateLog, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 30;
        $stepsMapping = ['step_1' => 28, 'step_2' => 29, 'step_3' => 30];

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  Start of Upfront createAPInvoicePrem for : '.$sendUpdateLog->code.' ');

        $isLiveApiCallStep28 = true;
        $createAPInvoicePrem = self::createAPPremInvoicePayload($sageRequest, $sageRequestEmbeddedProduct, $isReversal);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  createAPInvoicePrem  Sent Already for '.$sendUpdateLog->code);
            $isLiveApiCallStep28 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send createAPInvoicePrem  for '.$sendUpdateLog->code);
            $resp = $this->sageApiService->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAr - '.$postedResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep28) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep29 = true;
            $readyToPostInvoiceAP = self::readyToPostAPPremInvoicePayload($postedResponse['BatchNumber'], $isReversal);
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  readyToPostInvoiceAP  Sent Already for '.$sendUpdateLog->code);
                $isLiveApiCallStep29 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send readyToPostInvoiceAP  for '.$sendUpdateLog->code);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' : Error while making AP invoice ready to post to sage';
                $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep29) {
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            $isLiveApiCallStep30 = true;
            $aPPostInvoices = self::postAPPremInvoicePayload($postedResponse['BatchNumber'], $isReversal);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  aPPostInvoices  Sent Already for '.$sendUpdateLog->code);
                $isLiveApiCallStep30 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Check status of  AP invoice batch '.$postedResponse['BatchNumber'].'  for '.$sendUpdateLog->code);
                    $aPInvoiceBatch = $this->sageApiService->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API : SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : Status of  AP invoice batch('.$postedResponse['BatchNumber'].'): ', extra: $aPInvoiceBatch);
                    if (! isset($aPInvoiceBatch['BatchStatus'])) {
                        $message = 'Upfront - AP Invoice batch status key not defined';
                        $returnMessage['message'] = $message;
                        $returnMessage['error'] = $message;

                        return $returnMessage;
                    }

                    if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API : SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : AP invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$sendUpdateLog->code);
                        $postedResponse = $aPPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API : SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send aPPostInvoices  for '.$sendUpdateLog->code);
                    $resp = $this->sageApiService->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' Error while making AP invoices Posted to sage';
                $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' aPPostInvoices failed';

                return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - SAGE API: SendUpdate Code: '.$sendUpdateLog->code.' - aPPostInvoices completed successfully');
                if ($isLiveApiCallStep30) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }
        } else {
            $errorMessage = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' : Ap invoice prem failed from sage';
            $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' : createAPInvoicePrem  failed';

            return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - End of Upfront createAPInvoicePrem for SendUpdate Code: '.$sendUpdateLog->code.' - Reversal of EP code: '.$sukoonMedXEPTransaction->code);

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$sukoonMedXEPTransaction->code.' AP Premium invoice created on sage';

        return $returnMessage;
    }

    public function applyPaymentARInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$quote, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  Start applyPaymentARInvoices for : '.$quote->code.' ');
        $totalSteps = 12;
        $currentStep = 10;
        $isLiveApiCallStep10 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : createPaymentReceiptOneInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep10 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send createPaymentReceiptOneInvoice  for '.$quote->code);
            $payLoadOptions = self::createApplyPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct);
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep10) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' Error while making split prepayments to sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.' createPaymentReceiptOneInvoice failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : createPaymentReceiptOneInvoice  - BatchNumber : '.$batchNumber.' completed successfully');
        // 14
        $currentStep = 11;
        $isLiveApiCallStep11 = true;
        $readyToPostReceiptAr = self::readyToPostApplyPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep11 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.'  : Error while making Apply payment ready to post to sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.'  : readyToPostReceiptAr failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : readyToPostReceiptAr completed successfully');
            if ($isLiveApiCallStep11) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        }

        // 15
        $currentStep = 12;
        $isLiveApiCallStep12 = true;
        $aRPostReceipts = self::postApplyPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep12 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Status of  AR Prepayment Receipts batch('.$batchNumber.') : ', extra: $aRReceiptBatch);
                if (! isset($aRReceiptBatch['BatchStatus'])) {
                    $message = ' EP code: '.$sukoonMedXEPTransaction->code.'  :Apply Upfront Payment batch status key not defined';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  AR Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send aRPostReceipts  for '.$quote->code);
                $resp = $this->sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.'  : Error while making Apply payment Posted to sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.'  :aRPostReceipts failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : aRPostReceipts completed successfully');
        if ($isLiveApiCallStep12) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : End applyPaymentARInvoices for : '.$quote->code.' ');
        $returnMessage['status'] = true;
        $returnMessage['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' Prepayments applied on sage';

        return $returnMessage;

    }

    public static function createEmbeddedProductPayload($sukoonMedXTransaction, $viewQuotePolicyApiLog)
    {
        $insuranceProvider = $viewQuotePolicyApiLog->insuranceProvider;
        $viewQuotePolicyApiResponse = json_decode($viewQuotePolicyApiLog->response);

        $epRefCode = $sukoonMedXTransaction->code;
        $policyNumber = $viewQuotePolicyApiResponse->policy_number;

        $originalInsurerTaxInvoiceNumber = $viewQuotePolicyApiResponse->additional_data->tax_invoice_document_number;
        $originalCommissionTaxInvoiceNumber = $viewQuotePolicyApiResponse->additional_data->tax_invoice_buyer_document_number;

        $insurerTaxInvoiceNumber = self::formatDocNumber($originalInsurerTaxInvoiceNumber);
        $commissionTaxInvoiceNumber = self::formatDocNumber($originalCommissionTaxInvoiceNumber);

        $sageRequestEmbeddedProduct = new stdClass;
        $sageRequestEmbeddedProduct->tapChargeId = $sukoonMedXTransaction?->payment->paymentSplits->first()?->paymentCharges?->transaction_id;
        $sageRequestEmbeddedProduct->epSageReceiptId = null;
        $sageRequestEmbeddedProduct->invoiceDescription = $epRefCode.'.'.$policyNumber;
        $sageRequestEmbeddedProduct->policyNumber = $policyNumber;
        $sageRequestEmbeddedProduct->sageVendorId = $insuranceProvider->sage_vendor_id;
        $sageRequestEmbeddedProduct->sageCustomerNumber = $insuranceProvider->sage_insurer_customer_id;
        $sageRequestEmbeddedProduct->sageInsurerGlLiabilityAccount = $insuranceProvider->gl_liaiblity_account;
        $sageRequestEmbeddedProduct->insurerName = $insuranceProvider->text;
        $sageRequestEmbeddedProduct->collectionAmount = $viewQuotePolicyApiResponse->payments[0]->amount;
        $sageRequestEmbeddedProduct->commissionTaxInvoiceNumber = (string) mb_substr($commissionTaxInvoiceNumber, -18);
        $sageRequestEmbeddedProduct->originalCommissionTaxInvoiceNumber = $originalCommissionTaxInvoiceNumber;
        $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber = (string) mb_substr($insurerTaxInvoiceNumber, -18);
        $sageRequestEmbeddedProduct->originalInsurerTaxInvoiceNumber = $originalInsurerTaxInvoiceNumber;
        $sageRequestEmbeddedProduct->createdOn = Carbon::createFromFormat('d/m/Y', $viewQuotePolicyApiResponse->created_on)->format(env('DATE_FORMAT_ONLY'));
        $sageRequestEmbeddedProduct->taxAmount = $viewQuotePolicyApiResponse->pricing->tax_amount;
        $sageRequestEmbeddedProduct->policyPrice = $viewQuotePolicyApiResponse->pricing->policy_price;
        $sageRequestEmbeddedProduct->totalPrice = $viewQuotePolicyApiResponse->pricing->total_price;
        $sageRequestEmbeddedProduct->paymentAmount = $viewQuotePolicyApiResponse->payments[0]->amount;
        $sageRequestEmbeddedProduct->brokerCommissionAmount = $viewQuotePolicyApiResponse->additional_data->broker_commission_amount;
        $sageRequestEmbeddedProduct->brokerCommissionVatAmount = $viewQuotePolicyApiResponse->additional_data->broker_commission_vat_amount;
        $sageRequestEmbeddedProduct->brokerCommissionTotalAmount = $viewQuotePolicyApiResponse->additional_data->broker_commission_total_amount;
        $sageRequestEmbeddedProduct->startDate = Carbon::createFromFormat('d/m/Y', $viewQuotePolicyApiResponse->start_date)->format(env('DATE_FORMAT_ONLY'));
        $sageRequestEmbeddedProduct->endDate = Carbon::createFromFormat('d/m/Y', $viewQuotePolicyApiResponse->end_date)->format(env('DATE_FORMAT_ONLY'));

        return $sageRequestEmbeddedProduct;

    }

    private static function createARPaymentReceiptsPayload($sageRequest, $sageRequestEmbeddedProduct)
    {
        $optionalFields = self::createEPPrepaymentOptionalFields($sageRequest, $sageRequestEmbeddedProduct);

        $entryType = SageEnum::SCT_STRAIGHT;
        $customerNumber = $sageRequest->customerId;
        $bankCode = SageEnum::BANK_CODE;
        $paymentCode = SageEnum::PAYMENT_CODE;
        $bankReceiptAmount = roundNumber(floatval($sageRequestEmbeddedProduct->collectionAmount));
        $checkReceiptNumber = 'N/A';

        $payLoad = [
            'BatchRecordType' => 'CA',
            'BankCode' => $bankCode,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $customerNumber,
                    'BankCode' => $bankCode,
                    'BankReceiptAmount' => $bankReceiptAmount,
                    'CheckReceiptNumber' => $checkReceiptNumber,
                    'PaymentCode' => $paymentCode,
                    'ReceiptTransactionType' => 'Prepayment',
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $customerNumber,
                            'ReceiptTransactionType' => 'Prepayment',
                        ],
                    ],
                    'ReceiptAdjustmentOptionalField' => $optionalFields,
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_CREATE_AR_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    private static function createEPPrepaymentOptionalFields($sageRequest, $sageRequestEmbeddedProduct)
    {
        $optionalArray = [
            [
                'OptionalField' => 'CHEQUENO',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'DEPARTMENT',
                'Value' => $sageRequest->advisorDepartment,
            ],
            [
                'OptionalField' => 'INSURER',
                'Value' => $sageRequestEmbeddedProduct->insurerName,
            ],
            [
                'OptionalField' => 'LINEOFBUSNSS',
                'Value' => $sageRequest->mainClassInsurance,
            ],
            [
                'OptionalField' => 'ORICOMTAXNUM',
                'Value' => $sageRequestEmbeddedProduct->originalCommissionTaxInvoiceNumber,
            ],
            [
                'OptionalField' => 'INSTAXINVNO',
                'Value' => $sageRequestEmbeddedProduct->originalInsurerTaxInvoiceNumber,
            ],
            [
                'OptionalField' => 'INSTAXINVAMT',
                'Value' => $sageRequestEmbeddedProduct->totalPrice,
            ],
            [
                'OptionalField' => 'PAYMENTGTWAY',
                'Value' => 'TAP',
            ],
            [
                'OptionalField' => 'PAYMENTMETHD',
                'Value' => 'Credit Card',
            ],
            [
                'OptionalField' => 'POLICY',
                'Value' => $sageRequestEmbeddedProduct->policyNumber,
            ],
            [
                'OptionalField' => 'POLICYBKNGDT',
                'Value' => $sageRequest->bookingDate,
            ],
            [
                'OptionalField' => 'REFID',
                'Value' => $sageRequest->quoteCode,
            ],
            [
                'OptionalField' => 'SUREFID',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'ENDORSEMENT',
                'Value' => 'N/A',
            ],
            /* [
                'OptionalField' => 'ENDORSEMENTNUMBER',
                'Value' => 'N/A',
            ], */
            /* [
                'OptionalField' => 'INSURER RECEIPT NUMBER',
                'Value' => 'N/A',
            ], */
        ];

        return $optionalArray;
    }

    public static function readyToPostARPaymentReceiptPayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_RTP_AR_PP_REC,
            'entry_type' => $entryType,
        ];
    }
    public static function postARPaymentReceiptPayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchType' => 'CA',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AR/ARPostReceiptsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_POST_AR_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    private function createARPremAndComInvoicePayload($request, $sageRequestEmbeddedProduct, $isReversal = false)
    {
        $sageRequestType = SageEnum::EP_SRT_CREATE_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;

        // Payload creation logic for default scenario
        $taxClass = 1;
        $premiumDescription = 'P.'.$sageRequestEmbeddedProduct->invoiceDescription;
        $commissionDescription = 'C.'.$sageRequestEmbeddedProduct->invoiceDescription;
        $createdOn = $sageRequestEmbeddedProduct->createdOn;
        $createdOnDate = Carbon::parse($createdOn)->format(SagePayloadFactory::instanceData()->sage_api_date_format);
        $optionalFields = self::createOptionalFields($request, $sageRequestEmbeddedProduct);

        $payLoad = [
            'Invoices' => [
                [
                    'CustomerNumber' => $request->customerId,
                    'DocumentNumber' => $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $createdOnDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $createdOnDate,
                    'AsOfDate' => $createdOnDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => roundNumber($sageRequestEmbeddedProduct->taxAmount),
                    'DocumentTotalBeforeTax' => roundNumber($sageRequestEmbeddedProduct->policyPrice),
                    'DocumentTotalIncludingTax' => roundNumber($sageRequestEmbeddedProduct->totalPrice),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(SagePayloadFactory::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $premiumDescription,
                            'TaxClass1' => 5,
                            'RevenueAccount' => $sageRequestEmbeddedProduct->sageInsurerGlLiabilityAccount,
                            'ExtendedAmountWithTIP' => roundNumber($sageRequestEmbeddedProduct->totalPrice),
                            'ExtendedAmountWithoutTIP' => roundNumber($sageRequestEmbeddedProduct->policyPrice),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $createdOnDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
                [
                    'CustomerNumber' => $sageRequestEmbeddedProduct->sageCustomerNumber,
                    'DocumentNumber' => $sageRequestEmbeddedProduct->commissionTaxInvoiceNumber,
                    'InvoiceDescription' => $commissionDescription,
                    'DocumentDate' => $createdOnDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $createdOnDate,
                    'AsOfDate' => $createdOnDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => $taxClass,
                    'DocumentTotalBeforeTax' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionAmount),
                    'DocumentTotalIncludingTax' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionTotalAmount),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(SagePayloadFactory::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $taxClass,
                            'TaxAmount1' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionVatAmount),
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionTotalAmount),
                            'ExtendedAmountWithoutTIP' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionAmount),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $createdOnDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
            ],
        ];

        if ($isReversal) {
            $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote';
            $payLoad['Invoices'][0]['DocumentNumber'] = $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber.'-REV';
            $payLoad['Invoices'][0]['ApplytoDocument'] = $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber;

            $payLoad['Invoices'][1]['DocumentType'] = 'CreditNote';
            $payLoad['Invoices'][1]['DocumentNumber'] = $sageRequestEmbeddedProduct->commissionTaxInvoiceNumber.'-REV';
            $payLoad['Invoices'][1]['ApplytoDocument'] = $sageRequestEmbeddedProduct->commissionTaxInvoiceNumber;

            $sageRequestType = SageEnum::EP_SRT_CREATE_AR_PREM_COMM_INV_REV;
            $entryType = SageEnum::SCT_REVERSAL;
        }

        return [
            'endPoint' => 'AR/ARInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostARPremAndCommInvoicePayload($batchNumber, $isReversal = false)
    {
        $sageRequestType = SageEnum::EP_SRT_RTP_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;
        if ($isReversal) {
            $sageRequestType = SageEnum::EP_SRT_RTP_AR_PREM_COMM_INV_REV;
            $entryType = SageEnum::SCT_REVERSAL;
        }
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AR/ARInvoiceBatches'.'('.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function postARPremAndCommInvoicePayload($batchNumber, $isReversal = false)
    {
        $sageRequestType = SageEnum::EP_SRT_POST_AR_PREM_COMM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;
        if ($isReversal) {
            $sageRequestType = SageEnum::EP_SRT_POST_AR_PREM_COMM_INV_REV;
            $entryType = SageEnum::SCT_REVERSAL;
        }
        $payLoad = [
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',
        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AR/ARPostInvoices'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => $entryType,
        ];
    }

    private static function createOptionalFields($request, $sageRequestEmbeddedProduct)
    {
        $optionalArray = [
            [
                'OptionalField' => 'CCCODE',
                'Value' => $sageRequestEmbeddedProduct->tapChargeId,
            ],
            [
                'OptionalField' => 'ENDORSEMENT',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'EXPIRY',
                'Value' => Carbon::parse($sageRequestEmbeddedProduct->endDate)->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT')),
            ],
            [
                'OptionalField' => 'INCEPTION',
                'Value' => Carbon::parse($sageRequestEmbeddedProduct->startDate)->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT')),
            ],
            [
                'OptionalField' => 'INSURED',
                'Value' => $request->insured,
            ],
            [
                'OptionalField' => 'MAINCLASS',
                'Value' => $request->mainClassInsurance,
            ],
            [
                'OptionalField' => 'MANAGER',
                'Value' => $request->manager,
            ],
            [
                'OptionalField' => 'PDC',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'POLICY',
                'Value' => $sageRequestEmbeddedProduct->policyNumber,
            ],
            [
                'OptionalField' => 'POLICYHOLDER',
                'Value' => $request->policyHolder,
            ],
            [
                'OptionalField' => 'POLICYISSUER',
                'Value' => strval($sageRequestEmbeddedProduct->insurerName),
            ],
            [
                'OptionalField' => 'PREMIUM',
                'Value' => strval($sageRequestEmbeddedProduct->totalPrice),
            ],
            [
                'OptionalField' => 'PREMIUMVAT',
                'Value' => strval($sageRequestEmbeddedProduct->taxAmount), // this variable initially defined as String, Sage Request break if it does not coverted to String
            ],
            [
                'OptionalField' => 'REQUESTTYPE',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'SALESPERSON',
                'Value' => $request->advisorName,
            ],
            [
                'OptionalField' => 'SUBCLASS',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'CNTYPE',
                'Value' => 'N/A',
            ],
            [
                'OptionalField' => 'COLLECTS',
                'Value' => $request->premiumCollectedBy,
            ],
            [
                'OptionalField' => 'COMMRATE',
                'Value' => '',
            ],
            [
                'OptionalField' => 'STATE',
                'Value' => 'DXB',
            ],
            [
                'OptionalField' => 'ORITAXNUM',
                'Value' => $sageRequestEmbeddedProduct->originalInsurerTaxInvoiceNumber,
            ],
            [
                'OptionalField' => 'ORICOMTAXNUM',
                'Value' => $sageRequestEmbeddedProduct->originalCommissionTaxInvoiceNumber,
            ],
        ];

        return $optionalArray;
    }

    public static function createAPPremInvoicePayload($request, $sageRequestEmbeddedProduct, $isReversal = false)
    {
        $optionalFields = self::createOptionalFields($request, $sageRequestEmbeddedProduct);
        // Additional Option Field just for AP Invoice
        $optionalFields[] = [
            'OptionalField' => 'IGTC',
            'Value' => 'N',
        ];
        $premiumDescription = 'P.'.$sageRequestEmbeddedProduct->invoiceDescription;
        $createdOn = $sageRequestEmbeddedProduct->createdOn;
        $createdOnDate = Carbon::parse($createdOn)->format(SagePayloadFactory::instanceData()->sage_api_date_format);

        $payLoad = [
            'Invoices' => [
                [
                    'VendorNumber' => $sageRequestEmbeddedProduct->sageVendorId, // use vender api to create vender in sage
                    'DocumentNumber' => $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber,
                    'InvoiceDescription' => $premiumDescription,
                    'DocumentDate' => $createdOnDate,
                    'CurrencyCode' => 'AED',
                    'DueDate' => $createdOnDate,
                    'AsOfDate' => $createdOnDate,
                    'TaxGroup' => 'VAT',
                    'TaxClass1' => 5,
                    'TaxAmount1' => roundNumber($sageRequestEmbeddedProduct->taxAmount),
                    'DocumentTotalBeforeTaxes' => roundNumber($sageRequestEmbeddedProduct->policyPrice),
                    'DocumentTotalIncludingTax' => roundNumber($sageRequestEmbeddedProduct->totalPrice),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(SagePayloadFactory::instanceData()->sage_api_date_format), // Add date format because caught an error while calling sage for Send update
                    'InvoiceDetails' => [
                        [
                            'DistributionDescription' => $premiumDescription,
                            'TaxClass1' => 5,
                            'GLAccount' => $sageRequestEmbeddedProduct->sageInsurerGlLiabilityAccount,
                            'DistributedAmount' => roundNumber($sageRequestEmbeddedProduct->totalPrice),
                            'DistributedAmountBeforeTaxes' => roundNumber($sageRequestEmbeddedProduct->policyPrice),
                        ],
                    ],
                    'InvoicePaymentSchedules' => [
                        [
                            'DueDate' => $createdOnDate,
                        ],
                    ],
                    'InvoiceOptionalFields' => $optionalFields,
                ],
            ],
        ];

        $sageRequestType = SageEnum::EP_SRT_CREATE_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;
        if ($isReversal) {
            $payLoad['Invoices'][0]['DocumentType'] = 'CreditNote';
            $payLoad['Invoices'][0]['DocumentNumber'] = $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber.'-REV';
            $payLoad['Invoices'][0]['ApplytoDocument'] = $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber;
            $sageRequestType = SageEnum::EP_SRT_CREATE_AP_PREM_INV_REV;
            $entryType = SageEnum::SCT_REVERSAL;
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostAPPremInvoicePayload($batchNumber, $isReversal = false)
    {
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        $sageRequestType = SageEnum::EP_SRT_RTP_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;
        if ($isReversal) {
            $sageRequestType = SageEnum::EP_SRT_RTP_AP_PREM_INV_REV;
            $entryType = SageEnum::SCT_REVERSAL;
        }

        return [
            'endPoint' => 'AP/APInvoiceBatches'.'('.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function postAPPremInvoicePayload($batchNumber, $isReversal = false)
    {
        $payLoad = [
            'ProcessAllBatches' => 'Donotpostallbatches',
            'FromBatch' => $batchNumber,
            'ToBatch' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        $sageRequestType = SageEnum::EP_SRT_POST_AP_PREM_INV;
        $entryType = SageEnum::SCT_STRAIGHT;
        if ($isReversal) {
            $sageRequestType = SageEnum::EP_SRT_POST_AP_PREM_INV_REV;
            $entryType = SageEnum::SCT_REVERSAL;
        }

        return [
            'endPoint' => 'AP/APPostInvoices'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType,
            'entry_type' => $entryType,
        ];
    }

    public static function createApplyPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchRecordType' => 'CA',
            'BankCode' => SageEnum::BANK_CODE,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sageRequest->customerId,
                    'BankCode' => SageEnum::BANK_CODE,
                    'ReceiptTransactionType' => 'Receipt',
                    'AppliedReceiptsAdjustments' => [
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $sageRequest->customerId,
                            'DocumentNumber' => $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber,
                            'PaymentNumber' => 1,
                            'ReceiptTransactionType' => 'Receipt',
                            'CustomerReceiptAmount' => roundNumber($sageRequestEmbeddedProduct->totalPrice),
                        ],
                        [
                            'BatchType' => 'CA',
                            'CustomerNumber' => $sageRequest->customerId,
                            'DocumentNumber' => $sageRequestEmbeddedProduct->epSageReceiptId,
                            'PaymentNumber' => 1,
                            'ReceiptTransactionType' => 'Receipt',
                            'CustomerReceiptAmount' => -roundNumber($sageRequestEmbeddedProduct->paymentAmount),
                        ],
                    ],
                ],
            ],
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_CREATE_APPLY_PAYMENT_RECEIPT,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostApplyPrepaymentPayload($batchNumber)
    {
        $sageRequestType = SageEnum::EP_SRT_READY_TO_POST_APPLY_PAYMENT_RECEIPT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AR/ARReceiptAndAdjustmentBatches'.'(BatchRecordType=\'CA\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => SageEnum::SCT_STRAIGHT,
        ];
    }

    public static function postApplyPrepaymentPayload($batchNumber)
    {
        $sageRequestType = SageEnum::EP_SRT_POST_APPLY_PAYMENT_RECEIPT;
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchType' => 'CA',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',

        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AR/ARPostReceiptsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => $entryType,
        ];
    }

    public function updateAndLogEPBookingStatus($embeddedTransaction, $status, $logFor = null)
    {
        $logFor = $logFor ?? self::CLASSNAME.' fn: '.__FUNCTION__;
        LoggerService::info($logFor.' Sage Booking - Embedded Product : EP Transaction Code : '.$embeddedTransaction->code.', - Updating Sage Booking Status to : '.$status.' - Current Status: '.$embeddedTransaction->sage_status_id);
        if ($embeddedTransaction->sage_status_id != $status) {
            $embeddedTransaction->update(['sage_status_id' => $status]);
            LoggerService::info($logFor.' Sage Booking - Embedded Product : EP Transaction Code : '.$embeddedTransaction->code.', - Sage Booking Status Updated to : '.$status);
        }
    }

    private static function createAPPaymentReceiptsPayload($sageRequest, $sageRequestEmbeddedProduct)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $optionalFields = self::createEPPrepaymentOptionalFields($sageRequest, $sageRequestEmbeddedProduct);
        $optionalFields[] = [
            'OptionalField' => 'INSURERRCTNO',
            'Value' => 'N/A',
        ];

        $vendorNumber = $sageRequestEmbeddedProduct->sageVendorId;
        $bankCode = SageEnum::BANK_CODE;
        $bankReceiptAmount = roundNumber(floatval($sageRequestEmbeddedProduct->collectionAmount), 2);

        // Payment code logic - for embedded products, we typically use standard bank code
        $entryDescription = 'CLIENT DIRECT PAYMENT TO '.$sageRequestEmbeddedProduct->insurerName;

        $payLoad = [
            'BatchSelector' => 'PY',
            'Description' => $entryDescription,
            'BankCode' => $bankCode,
            'PaymentsAdjustments' => [
                [
                    'BatchType' => 'PY',
                    'VendorNumber' => $vendorNumber,
                    'EntryDescription' => $entryDescription,
                    'PaymentTransactionType' => 'Prepayment',
                    'BankCode' => $bankCode,
                    'TotalPrepayVendorCurrency' => $bankReceiptAmount,
                    'AppliedPayments' => [
                        [
                            'BatchType' => 'PY',
                            'VendorNumber' => $vendorNumber,
                            'TransactionType' => 'PrepaymentPosted',
                        ],
                    ],
                    'PaymentAdjustmentOptionalField' => $optionalFields,
                ],
            ],
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_CREATE_AP_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostAPPaymentReceiptPayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches'.'(BatchSelector=\'PY\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_RTP_AP_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public static function postAPPaymentReceiptPayload($batchNumber)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchType' => 'PY',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',
        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AP/APPostPaymentsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_SRT_POST_AP_PP_REC,
            'entry_type' => $entryType,
        ];
    }

    public function applyPaymentAPInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$quote, $sukoonMedXEPTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  Start applyPaymentAPInvoices for : '.$quote->code.' ');

        $totalSteps = 15;
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : createAPPaymentReceiptOneInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep13 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.' :  Send createAPPaymentReceiptOneInvoice  for '.$quote->code);
            $payLoadOptions = self::createApplyAPPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct);
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.' Error while making split prepayments to sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.' createAPPaymentReceiptOneInvoice failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : createAPPaymentReceiptOneInvoice  - BatchNumber : '.$batchNumber.' completed successfully');

        // Step 14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        $readyToPostReceiptAP = self::readyToPostApplyAPPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  readyToPostReceiptAP  Sent Already for '.$quote->code);
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send readyToPostReceiptAP  for '.$quote->code);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAP['endPoint'], $readyToPostReceiptAP['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.'  : Error while making Apply AP payment ready to post to sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.'  : readyToPostReceiptAP failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $readyToPostReceiptAP, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : readyToPostReceiptAP completed successfully');
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        }

        // Step 15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        $aPPostReceipts = self::postApplyAPPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  aPPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Check status of  AP Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aPReceiptBatch = $this->sageApiService->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$batchNumber.')', [], 'GET');
                $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Status of  AP Prepayment Receipts batch('.$batchNumber.') : ', extra: $aPReceiptBatch);
                if (! isset($aPReceiptBatch['BatchStatus'])) {
                    $message = ' EP code: '.$sukoonMedXEPTransaction->code.'  :Apply AP Upfront Payment batch status key not defined';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aPPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  :  Send aPPostReceipts  for '.$quote->code);
                $resp = $this->sageApiService->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$sukoonMedXEPTransaction->code.'  : Error while making Apply AP payment Posted to sage';
            $message = ' EP code: '.$sukoonMedXEPTransaction->code.'  :aPPostReceipts failed';

            return $this->sageApiService->logErrorAndReturn([$sukoonMedXEPTransaction, $message, $errorMessage, $aPPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : '.$quote->code.' : aPPostReceipts completed successfully');
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aPPostReceipts, $postedResponse, $sukoonMedXEPTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$sukoonMedXEPTransaction->code.'  : End applyPaymentAPInvoices for : '.$quote->code.' ');
        $returnMessage['status'] = true;
        $returnMessage['message'] = ' EP code: '.$sukoonMedXEPTransaction->code.' AP Prepayments applied on sage';

        return $returnMessage;
    }

    public static function createApplyAPPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchSelector' => 'PY',
            'Description' => 'CLIENT PAYMENT MAPPING',
            'BankCode' => SageEnum::BANK_CODE,
            'PaymentsAdjustments' => [
                [
                    'BatchType' => 'PY',
                    'VendorNumber' => $sageRequestEmbeddedProduct->sageVendorId,
                    'EntryDescription' => 'CLIENT PAYMENT MAPPING',
                    'PaymentTransactionType' => 'Payment',
                    'AppliedPayments' => [
                        [
                            'BatchType' => 'PY',
                            'VendorNumber' => $sageRequestEmbeddedProduct->sageVendorId,
                            'DocumentNumber' => $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber,
                            'PaymentNumber' => 1,
                            'TransactionType' => 'PaymentPosted',
                            'PaymentAmount' => roundNumber($sageRequestEmbeddedProduct->totalPrice),
                        ],
                        [
                            'BatchType' => 'PY',
                            'VendorNumber' => $sageRequestEmbeddedProduct->sageVendorId,
                            'DocumentNumber' => $sageRequestEmbeddedProduct->epSageAPReceiptId,
                            'PaymentNumber' => 1,
                            'TransactionType' => 'PaymentPosted',
                            'PaymentAmount' => -roundNumber($sageRequestEmbeddedProduct->paymentAmount),
                        ],
                    ],
                ],
            ],
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches',
            'payload' => $payLoad,
            'sage_request_type' => SageEnum::EP_AP_SRT_CREATE_APPLY_PAYMENT_RECEIPT,
            'entry_type' => $entryType,
        ];
    }

    public static function readyToPostApplyAPPrepaymentPayload($batchNumber)
    {
        $sageRequestType = SageEnum::EP_AP_SRT_READY_TO_POST_APPLY_PAYMENT_RECEIPT;
        $payLoad = [
            'BatchStatus' => 'ReadyToPost',
        ];

        return [
            'endPoint' => 'AP/APPaymentAndAdjustmentBatches'.'(BatchSelector=\'PY\',BatchNumber='.$batchNumber.')',
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => SageEnum::SCT_STRAIGHT,
        ];
    }

    public static function postApplyAPPrepaymentPayload($batchNumber)
    {
        $sageRequestType = SageEnum::EP_AP_SRT_POST_APPLY_PAYMENT_RECEIPT;
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchType' => 'PY',
            'PostAllBatches' => 'Donotpostallbatches',
            'PostBatchFrom' => $batchNumber,
            'PostBatchTo' => $batchNumber,
            'ActionSelector' => 'string',
            'UpdateOperation' => 'Unspecified',
        ];

        $sign = '$process';
        $val = "('".$sign."')";

        return [
            'endPoint' => 'AP/APPostPaymentsAndAdjustments'.$val,
            'payload' => $payLoad,
            'sage_request_type' => $sageRequestType ?? null,
            'entry_type' => $entryType,
        ];
    }

    /*
     * We are having duplicate insurer tax and commission tax invoice number which are causing issue with sage booking, as same invoice numbers were being issued for
     * other Leads in the past, so we are adding asterisk for uniqueness, there have been some db changes for this already so I am  adding asterisk conditionally so
     * it would not mess with reversal of those entries
     * */
    private static function formatDocNumber($docNumber)
    {
        return substr($docNumber, -1) === '*' ? $docNumber : $docNumber.'*';
    }

}
