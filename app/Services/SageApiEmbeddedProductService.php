<?php

namespace App\Services;

use App\Enums\EmbeddedProductEnum;
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
        $isInAllowedInsuranceProvider = in_array($insuranceProvider?->code, $this->sageApiService->allowedProviderForSageEPBooking());
        if (! $isInAllowedInsuranceProvider) {
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
        $sageRequest->epShortCode = $epTransaction->product?->embeddedProduct?->short_code;

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

    public function getInsurerRequestResponse($quote, $epShortCode, $insuranceProviderId = null)
    {
        return match ($epShortCode) {
            EmbeddedProductEnum::ECB => self::getInsurerRequestResponseForECB($quote, $insuranceProviderId),
            default => self::getInsurerRequestResponseForSukoonMedXRedx($quote, $insuranceProviderId),
        };
    }

    public function getInsurerRequestResponseForECB($quote, $insuranceProviderId = null)
    {
        $insurerRequestResponse = InsurerRequestResponse::when($insuranceProviderId, function ($query, $insuranceProviderId) {
            return $query->where('provider_id', $insuranceProviderId);
        })->where([
            'quote_uuid' => $quote->uuid, 'status' => 'passed', 'execution_method' => 'GetPolicyDocuments',  'call_type' => 'EpEcb',
        ])->latest()->first();

        return $insurerRequestResponse;
    }

    public function getInsurerRequestResponseForSukoonMedXRedx($quote, $insuranceProviderId = null)
    {
        return InsurerRequestResponse::when($insuranceProviderId, function ($query, $insuranceProviderId) {
            return $query->where('provider_id', $insuranceProviderId);
        })->where([
            'quote_uuid' => $quote->uuid, 'status' => 'passed', 'execution_method' => 'viewQuotePolicy',  'call_type' => 'EmbeddedProduct',
        ])->latest()->first();
    }

    public function bookReversalOfEmbeddedProductOnSage($sageRequestDataArray, $epShortCode = null)
    {
        [$quote ,$sendUpdateLog, $sageRequest, $embeddedProductTransaction] = $sageRequestDataArray;

        LoggerService::startQuoteLogging($embeddedProductTransaction, LoggerFeatureEnum::SAGE_EP_BOOKING_REVERSAL);
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - SendUpdate Code: '.$sendUpdateLog->code.' - Embedded Product Booking Reversal started for EP Code: '.$embeddedProductTransaction->code);

        $insurerRequestResponse = $this->getInsurerRequestResponse($quote, $epShortCode);

        $createARInvoiceForEPLog = $embeddedProductTransaction?->sageApiLogs?->where('sage_request_type', SageEnum::EP_SRT_CREATE_AR_PREM_COMM_INV)->first();
        $createEPARPayload = json_decode($createARInvoiceForEPLog->sage_payload, true);
        $sageRequest->customerId = $createEPARPayload['Invoices'][0]['CustomerNumber'];

        $sageRequestEmbeddedProduct = self::createEmbeddedProductPayload($embeddedProductTransaction, $insurerRequestResponse, $epShortCode);
        $quoteTypeId = $sageRequest->quoteTypeId;

        $sageLogArray = $sendUpdateLog->sageApiLogs->keyBy('step')->toArray();
        // Create AR Commission and Premium Invoice
        $createARInvoicePremAndComm = $this->createARInvoicePremAndCommReversal([$sendUpdateLog, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray], true);
        if (! $createARInvoicePremAndComm['status']) {
            $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            return $createARInvoicePremAndComm;
        }

        // Create AP Premium Invoice
        $createAPInvoicePrem = $this->createAPPremInvoiceReversal([$sendUpdateLog, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray], true);
        if (! $createAPInvoicePrem['status']) {
            $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            return $createAPInvoicePrem;
        }

        $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_CANCELLED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' - Sage Booking - SendUpdate Code: '.$sendUpdateLog->code.' - Reversal of Embedded Product Booking Process Completed for EP Code: '.$embeddedProductTransaction->code);

        return ['status' => true, 'message' => 'Reversal of Embedded Product is Booked for SendUpdate Code : '.$sendUpdateLog->code.' and EP Code: '.$embeddedProductTransaction->code];
    }

    public function bookEmbeddedProductOnSage($sageRequestDataArray, $epShortCode = null)
    {
        [$quote ,$sageRequest, $embeddedProductTransaction] = $sageRequestDataArray;
        LoggerService::startQuoteLogging($embeddedProductTransaction, LoggerFeatureEnum::SAGE_EP_BOOKING);
        LoggerService::info('--------------------------------Embedded Product Sage booking process started-------------------------------');

        $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_QUEUED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);
        $insuranceProviderId = $embeddedProductTransaction?->product?->embeddedProduct?->insurance_provider_id;
        $insurerRequestResponse = $this->getInsurerRequestResponse($quote, $epShortCode, $insuranceProviderId);

        $sageRequestEmbeddedProduct = self::createEmbeddedProductPayload($embeddedProductTransaction, $insurerRequestResponse, $epShortCode);
        $quoteTypeId = $sageRequest->quoteTypeId;

        $sageLogArray = $embeddedProductTransaction->sageApiLogs->keyBy('step')->toArray();

        $tagName = QuoteTagEnums::EMBEDDED_PRODUCT_BOOKED_ON_SAGE;
        if (! in_array($epShortCode, [EmbeddedProductEnum::MDX, EmbeddedProductEnum::RDX])) {
            $tagName = QuoteTagEnums::EMBEDDED_PRODUCT_BOOKED_ON_SAGE.'_'.$epShortCode;
        }
        $isEmbeddedProductBookedOnSage = QuoteTag::where([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => $tagName,
            'value' => 1,
        ])->first();

        if (! $isEmbeddedProductBookedOnSage) {
            // Execute AR Prepayment Receipt Post Call
            $arReceiptCreationResponse = $this->createARPrepaymentReceipt([$quote, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $arReceiptCreationResponse['status']) {
                $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $arReceiptCreationResponse;
            }
            $sageRequestEmbeddedProduct->epSageReceiptId = $arReceiptCreationResponse['documentNumber'];

            // Execute AP Prepayment Receipt Post Call
            /*$apReceiptCreationResponse = $this->createAPPrepaymentReceipt([$quote, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $apReceiptCreationResponse['status']) {
                $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $apReceiptCreationResponse;
            }
            $sageRequestEmbeddedProduct->epSageAPReceiptId = $apReceiptCreationResponse['documentNumber'];*/

            // Create AR Commission and Premium Invoice
            $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$quote, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $createARInvoicePremAndComm['status']) {
                $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $createARInvoicePremAndComm;
            }

            // Create AP Premium Invoice
            $createAPInvoicePrem = $this->createAPPremInvoice([$quote, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $createAPInvoicePrem['status']) {
                $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $createAPInvoicePrem;
            }

            // Apply Prepayments AR Invoice
            $applyPaymentARInvoices = $this->applyPaymentARInvoices([$quote, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $applyPaymentARInvoices['status']) {
                $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $applyPaymentARInvoices;
            }

            // Apply Prepayments AP Invoice
            /*$applyPaymentAPInvoices = $this->applyPaymentAPInvoices([$quote, $embeddedProductTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray]);
            if (! $applyPaymentAPInvoices['status']) {
                $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_FAILED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

                return $applyPaymentAPInvoices;
            }*/

            $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_COMPLETED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);

            QuoteTag::create([
                'quote_type_id' => $quoteTypeId,
                'quote_uuid' => $quote->uuid,
                'name' => $tagName,
                'value' => 1,
            ]);

            LoggerService::info('Embedded Product Booked for: '.$quote->code, extra: [
                'QuoteCode' => $quote->code,
            ]);
        } else {
            $this->updateAndLogEPBookingStatus($embeddedProductTransaction, SageEmbeddedProductEnum::BOOKING_COMPLETED->id(), self::CLASSNAME.' fn: '.__FUNCTION__);
            LoggerService::info('Embedded Product Booked Already for: '.$quote->code, extra: [
                'QuoteCode' => $quote->code,
            ]);
        }

        LoggerService::info('--------------------------------Embedded Product Sage booking process completed-------------------------------');

        return ['status' => true, 'message' => 'Embedded Product is Booked for EP Code : '.$embeddedProductTransaction->code];
    }

    private function createARPrepaymentReceipt($sageRequestDataArray): array
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'documentNumber' => null];
        [$quote, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct , $sageLogArray] = $sageRequestDataArray;
        $quoteTypeId = $sageRequest->quoteTypeId;
        $isAlreadyPosted = false;

        $totalSteps = 3;
        LoggerService::info('Starting AR Prepayment Receipt creation for Embedded Product', extra: [
            'EPCode' => $embeddedTransaction->code,
        ]);

        $payLoadOptions = self::createARPaymentReceiptsPayload($sageRequest, $sageRequestEmbeddedProduct);
        $currentStep = 1;
        $isLiveApiCallStep1 = true;
        if (isset($sageLogArray[1]) && $sageLogArray[1]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep1 = false;
            $sageResponse = json_decode($sageLogArray[1]['response'], true);
            LoggerService::info('EP AR Prepayment Receipts already sent', extra: [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
        } else {
            $createPremiumPrepaymentResponse = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($createPremiumPrepaymentResponse, true);
            LoggerService::info('EP AR Prepayment Receipts batch created', extra: [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
        }

        if (isset($sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'])) {
            $response['documentNumber'] = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            if (! $embeddedTransaction->sage_ar_payment_receipt_id) {
                $embeddedTransaction->update(['sage_ar_payment_receipt_id' => $response['documentNumber']]);
            }
            if ($isLiveApiCallStep1) {
                LoggerService::info('Logging EP AR Prepayment Receipts batch posted successfully', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
                $this->logSageApiCall($payLoadOptions, $sageResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $currentStep = 2;
            $isLiveApiCallStep2 = true;
            $readyToPostReceiptAr = self::readyToPostARPaymentReceiptPayload($sageResponse['BatchNumber']);

            if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep2 = false;
                $readyToPostResponse = $sageLogArray[2]['response'];
                LoggerService::info('EP AR Prepayment Ready To Post already sent', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
            } else {
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
                LoggerService::info('Sending EP AR Prepayment Ready To Post to Sage', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                    'ReadyToPostResponse' => $readyToPostResponse,
                ]);
            }

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post EP AR Prepayment Ready To Post batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value'],
                    ]);

                    $aRReceiptBatch = $this->sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    $aRReceiptBatch = json_decode($aRReceiptBatch, true);
                    LoggerService::info('Checking status of EP AR Prepayment Receipts batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'BatchStatus' => $aRReceiptBatch['BatchStatus'] ?? 'Not found',
                    ]);

                    if (isset($aRReceiptBatch['BatchStatus']) && $aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info('EP AR Prepayment Receipts batch already posted', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aRReceiptBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get EP AR Prepayment Receipts batch status', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$embeddedTransaction->code.' Failed to get Prepayment Batch Status';

                        return $response;
                    } else {
                        LoggerService::info('Error while making EP AR Prepayment ready to post to Sage', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$embeddedTransaction->code.' Error while making ready to post to sage';

                        return $response;
                    }
                } else {
                    if ($isLiveApiCallStep2) {
                        LoggerService::info('Logging EP AR Prepayment Ready To Post batch successfully', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }
            } else {
                if ($isLiveApiCallStep2) {
                    LoggerService::info('Logging EP AR Prepayment Ready To Post batch successfully with empty response', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                    ]);
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            LoggerService::info('Posting EP AR Prepayment for Embedded Product', extra: [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
            $isLiveApiCallStep3 = true;
            $currentStep = 3;
            $aRPostReceipts = self::postARPaymentReceiptPayload($sageResponse['BatchNumber']);
            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[3]['response'], true);
                LoggerService::info('EP AR Prepayment Receipts batch already sent', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
            } else {
                if ($isAlreadyPosted && isset($aRPostReceipts) || isset($sageLogArray[3]) && $sageLogArray[3]['status'] == SageEnum::STATUS_FAIL) {
                    if ($isAlreadyPosted) {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].'  already posted :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                        $postedResponse = $aRPostReceipts['payload'];
                    } else {
                        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: check status of AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                        $aRReceiptBatch = $this->sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                        $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                        if (! isset($aRReceiptBatch['BatchStatus'])) {
                            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].'  failed to get status :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                            $response['message'] = 'EP  Ref:'.$embeddedTransaction->code.' Failed to get status of AR Prepayment Receipts batch';

                            return $response;
                        }

                        if (isset($aRReceiptBatch['BatchStatus']) && $aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            $postedResponse = $aRPostReceipts['payload'];
                            $isAlreadyPosted = true;
                        }
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: posting AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].' :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                    $postedResponse = $this->sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                    $postedResponse = json_decode($postedResponse, true);
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].'  posted :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                }
            }

            if (isset($postedResponse['error'])) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.' SAGE API Payments Error: Failed to post AR Receipts for batch '.$sageResponse['BatchNumber'], extra: [
                    'error' => $postedResponse['error'],
                ]);
                $response['message'] = ' EP code: '.$embeddedTransaction->code.' :  Error while posting to sage';
                $sageErrorMessage = $postedResponse['error']['message']['value'] ?? $postedResponse['error'] ?? null;
                if ($this->sageApiService->sageHasProcessingConflict($sageErrorMessage)) {
                    $response['message'] = SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE;
                }
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);

                return $response;
            } else {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].'  posted :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                if ($isLiveApiCallStep3) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API Payments: Logging AR Prepayment Receipts batch '.$sageResponse['BatchNumber'].'  posted :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code);
                    $this->logSageApiCall($aRPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            LoggerService::info('Successfully created EP AR Prepayment Receipt');
            $response['status'] = true;
            $response['message'] = ' EP code: '.$embeddedTransaction->code.' : Prepayment created';
        } else {
            LoggerService::info('Error: Document number not generated from Sage for EP AR Prepayment', extra: [
                'SageResponse' => $sageResponse,
            ]);
            $this->logSageApiCall($payLoadOptions, $sageResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
            $response['message'] = ' EP code: '.$embeddedTransaction->code.' : Document number not generated from sage - Ref:'.$quote->code;
        }

        return $response;
    }

    private function createAPPrepaymentReceipt($sageRequestDataArray): array
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'documentNumber' => null];
        [$quote, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct , $sageLogArray] = $sageRequestDataArray;
        $quoteTypeId = $sageRequest->quoteTypeId;
        $isAlreadyPosted = false;

        $totalSteps = 6;
        LoggerService::info('Starting AP Prepayment Receipt creation for Embedded Product');

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
            if (! $embeddedTransaction->sage_ap_payment_receipt_id) {
                $embeddedTransaction->update(['sage_ap_payment_receipt_id' => $response['documentNumber']]);
            }
            LoggerService::info('EP AP Prepayment Receipts batch created', extra: [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
            if ($isLiveApiCallStep4) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
            $currentStep = 5;
            $isLiveApiCallStep5 = true;
            $readyToPostReceiptAP = self::readyToPostAPPaymentReceiptPayload($sageResponse['BatchNumber']);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAP['endPoint'], $readyToPostReceiptAP['payload'], 'PATCH');

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post EP AP Prepayment Ready To Post batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value'],
                    ]);

                    $aPReceiptBatch = $this->sageApiService->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    LoggerService::info('Checking status of EP AP Prepayment Receipts batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'aPReceiptBatch' => $aPReceiptBatch,
                    ]);
                    $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                    if (isset($aPReceiptBatch['BatchStatus']) && $aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aPReceiptBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get EP AP Prepayment Receipts batch status', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$embeddedTransaction->code.' Failed to get AP Prepayment Batch Status';

                        return $response;
                    } else {
                        LoggerService::info('Error while making EP AP Prepayment ready to post to Sage', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $response['message'] = 'EP  Ref:'.$embeddedTransaction->code.' Error while making AP ready to post to sage';

                        return $response;
                    }
                } else {
                    $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            } else {
                if ($isLiveApiCallStep5) {
                    $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            LoggerService::info('Posting EP AP Prepayment for Embedded Product', extra: [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
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
                $this->logSageApiCall($aPPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            } else {
                if (isset($postedResponse['error'])) {
                    LoggerService::info('Error while posting EP AP Prepayment to Sage', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                    ]);
                    $response['message'] = ' EP code: '.$embeddedTransaction->code.' :  Error while posting AP to sage';
                    $this->logSageApiCall($aPPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);

                    return $response;
                } else {
                    if ($isLiveApiCallStep6) {
                        $this->logSageApiCall($aPPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }

            }

            LoggerService::info('Successfully created EP AP Prepayment Receipt');
            $response['status'] = true;
            $response['message'] = ' EP code: '.$embeddedTransaction->code.' : AP Prepayment created';
        } else {
            LoggerService::info('Error: Document number not generated from Sage for EP AP Prepayment');
            $this->logSageApiCall($payLoadOptions, $sageResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
            $response['message'] = ' EP code: '.$embeddedTransaction->code.' : Document number not generated from sage - Ref:'.$quote->code;
        }

        return $response;
    }

    private function createARInvoicePremAndComm($sageRequestDataArray)
    {
        [$quote, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;

        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 6;
        $stepsMapping = ['step_1' => 4, 'step_2' => 5, 'step_3' => 6];
        $isAlreadyPosted = false;

        LoggerService::info('Starting EP AR Invoice Premium and Commission creation');

        $isLiveApiCallStep1 = true;
        $payLoadOptions = self::createARPremAndComInvoicePayload($sageRequest, $sageRequestEmbeddedProduct);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('EP AR Invoice Premium and Commission already sent');
            $isLiveApiCallStep1 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending EP AR Invoice Premium and Commission to Sage');
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            LoggerService::info('EP AR Invoice Premium and Commission batch number - '.$sageResponse['BatchNumber']);
            if ($isLiveApiCallStep1) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $embeddedTransaction, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep2 = true;
            $readyToPostInvoiceAr = self::readyToPostARPremAndCommInvoicePayload($sageResponse['BatchNumber']);

            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep2 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                LoggerService::info('EP AR Invoice Premium and Commission ready to post already sent', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
            } else {
                LoggerService::info('Sending EP AR Invoice Premium and Commission ready to post to Sage', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making EP Ar invoice & prem ready to post to sage';
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post EP AR Invoice Premium and Commission Ready To Post batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value'],
                    ]);

                    $arInvoiceBatch = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    LoggerService::info('Checking status of EP AR Invoice Premium and Commission batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found',
                    ]);

                    if (isset($arInvoiceBatch['BatchStatus']) && $arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info('EP AR Invoice Premium and Commission batch already posted', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, '', $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($arInvoiceBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get EP AR Invoice Premium and Commission batch status', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = ' EP code: '.$embeddedTransaction->code.' : Failed to get status of AR Invoice Premium and Commission batch - '.$sageResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    } else {
                        LoggerService::info('Error while making EP AR Invoice Premium and Commission ready to post to sage', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = ' EP code: '.$embeddedTransaction->code.' : Failed to post AR Invoice Premium and Commission Ready To Post batch - '.$sageResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    }
                } else {
                    if ($isLiveApiCallStep2) {
                        LoggerService::info('Logging EP AR Invoice Premium and Commission Ready To Post batch successfully', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }
            } else {
                if ($isLiveApiCallStep2) {
                    LoggerService::info('Logging EP AR Invoice Premium and Commission Ready To Post batch successfully with empty response', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            $isLiveApiCallStep3 = true;
            $aRPostInvoices = self::postARPremAndCommInvoicePayload($sageResponse['BatchNumber']);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('EP AR Invoice Premium and Commission AR Post already sent');
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                if (($isAlreadyPosted && isset($aRPostInvoices)) || (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL)) {
                    if ($isAlreadyPosted) {
                        LoggerService::info('EP AR Invoice Premium and Commission batch already posted', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $postedResponse = $aRPostInvoices['payload'];
                    } else {
                        LoggerService::info('Checking status of EP AR Invoice Premium and Commission batch', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $arInvoiceBatch = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                        $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                        LoggerService::info('Status of EP AR Invoice Premium and Commission batch', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found',
                        ]);

                        if (! isset($arInvoiceBatch['BatchStatus'])) {
                            $message = ' EP code: '.$embeddedTransaction->code.' : Upfront - AR Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;

                            return $returnMessage;
                        }

                        if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('EP AR Invoice Premium and Commission batch already posted', extra : [
                                'BatchNumber' => $sageResponse['BatchNumber'],
                            ]);
                            $postedResponse = $aRPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info('Sending EP AR Invoice Premium and Commission AR Post to Sage');
                    $resp = $this->sageApiService->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = ' EP code: '.$embeddedTransaction->code.' : Error while making Ar invoice & prem Posted to sage';
                $message = ' EP code: '.$embeddedTransaction->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            }
            LoggerService::info('EP AR Invoice Premium and Commission AR Post completed successfully', extra : [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $embeddedTransaction, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        } else {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.' : Ar invoice & prem failed from sage';
            $message = ' EP code: '.$embeddedTransaction->code.' : createARInvoicePremAndComm  failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        LoggerService::info('Completed EP AR Invoice Premium and Commission creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Premium and Commission invoice created on sage for EP Code '.$embeddedTransaction->code;

        return $returnMessage;

    }

    private function createAPPremInvoice($sageRequestDataArray)
    {
        [$quote, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 9;
        $stepsMapping = ['step_1' => 7, 'step_2' => 8, 'step_3' => 9];
        $isAlreadyPosted = false;

        LoggerService::info('Starting EP AP Invoice Premium creation');

        $isLiveApiCallStep5 = true;
        $createAPInvoicePrem = self::createAPPremInvoicePayload($sageRequest, $sageRequestEmbeddedProduct);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('EP AP Invoice Premium already sent');
            $isLiveApiCallStep5 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending EP AP Invoice Premium to Sage');
            $resp = $this->sageApiService->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            LoggerService::info('EP AP Invoice Premium batch number - '.$postedResponse['BatchNumber']);
            if ($isLiveApiCallStep5) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $embeddedTransaction, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep6 = true;
            $readyToPostInvoiceAP = self::readyToPostAPPremInvoicePayload($postedResponse['BatchNumber']);

            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep6 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                LoggerService::info('EP AP Invoice Premium ready to post already sent', extra: [
                    'BatchNumber' => $postedResponse['BatchNumber'],
                ]);
            } else {
                LoggerService::info('Sending EP AP Invoice Premium ready to post to Sage', extra: [
                    'BatchNumber' => $postedResponse['BatchNumber'],
                ]);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making EP AP invoice ready to post to sage';
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post EP AP Invoice Premium Ready To Post batch', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value'],
                    ]);

                    $aPInvoiceBatch = $this->sageApiService->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    LoggerService::info('Checking status of EP AP Invoice Premium batch', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                        'BatchStatus' => $aPInvoiceBatch['BatchStatus'] ?? 'Not found',
                    ]);

                    if (isset($aPInvoiceBatch['BatchStatus']) && $aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostInvoiceAP, '', $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aPInvoiceBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get EP AP Invoice Premium batch status', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = ' EP code: '.$embeddedTransaction->code.' : Failed to get status of AP Invoice Premium batch - '.$postedResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    } else {
                        LoggerService::info('Error while making EP AP Invoice Premium ready to post to sage', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = ' EP code: '.$embeddedTransaction->code.' : Failed to post AP Invoice Premium Ready To Post batch - '.$postedResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    }
                } else {
                    if ($isLiveApiCallStep6) {
                        LoggerService::info('Logging EP AP Invoice Premium Ready To Post batch successfully', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }
            } else {
                if ($isLiveApiCallStep6) {
                    LoggerService::info('Logging EP AP Invoice Premium Ready To Post batch successfully with empty response', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $embeddedTransaction, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            $apBatchNumber = $postedResponse['BatchNumber'];
            $isLiveApiCallStep7 = true;
            $aPPostInvoices = self::postAPPremInvoicePayload($apBatchNumber);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('EP AP Invoice Premium AP Post already sent');
                $isLiveApiCallStep7 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                if (($isAlreadyPosted && isset($aPPostInvoices)) || (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL)) {
                    if ($isAlreadyPosted) {
                        LoggerService::info('EP AP Invoice Premium batch already posted', extra : [
                            'BatchNumber' => $apBatchNumber,
                        ]);
                        $postedResponse = $aPPostInvoices['payload'];
                    } else {
                        LoggerService::info('Checking status of EP AP Invoice Premium batch', extra : [
                            'BatchNumber' => $apBatchNumber,
                        ]);
                        $aPInvoiceBatch = $this->sageApiService->postToSage300('AP/APInvoiceBatches('.$apBatchNumber.')', [], 'GET');
                        $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                        LoggerService::info('Status of EP AP Invoice Premium batch', extra: [
                            'BatchNumber' => $apBatchNumber,
                            'BatchStatus' => $aPInvoiceBatch['BatchStatus'] ?? 'Not found',
                        ]);

                        if (! isset($aPInvoiceBatch['BatchStatus'])) {
                            $message = ' EP code: '.$embeddedTransaction->code.' : Upfront - AP Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;

                            return $returnMessage;
                        }

                        if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('EP AP Invoice Premium batch already posted', extra : [
                                'BatchNumber' => $apBatchNumber,
                            ]);
                            $postedResponse = $aPPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info('Sending EP AP Invoice Premium AP Post to Sage');
                    $resp = $this->sageApiService->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making EP AP invoices Posted to sage';
                $message = 'aPPostInvoices failed';

                return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            } else {
                LoggerService::info('EP AP Invoice Premium AP Post completed successfully', extra : [
                    'BatchNumber' => $apBatchNumber,
                ]);
                if ($isLiveApiCallStep7) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $embeddedTransaction, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }
        } else {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.' : Ap invoice prem failed from sage';
            $message = ' EP code: '.$embeddedTransaction->code.' : createAPInvoicePrem  failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        LoggerService::info('Completed EP AP Invoice Premium creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Premium invoice created on sage for EP Code '.$embeddedTransaction->code;

        return $returnMessage;
    }

    private function createARInvoicePremAndCommReversal($sageRequestDataArray, $isReversal = false)
    {
        [$sendUpdateLog, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;

        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 27;
        $stepsMapping = ['step_1' => 25, 'step_2' => 26, 'step_3' => 27];
        $isAlreadyPosted = false;

        LoggerService::info('Starting Reversal of EP AR Invoice Premium and Commission creation');

        $isLiveApiCallStep1 = true;
        $payLoadOptions = self::createARPremAndComInvoicePayload($sageRequest, $sageRequestEmbeddedProduct, $isReversal);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Reversal of EP AR Invoice Premium and Commission already sent');
            $isLiveApiCallStep1 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending Reversal of EP AR Invoice Premium and Commission to Sage');
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            LoggerService::info('Reversal of EP AR Invoice Premium and Commission batch number - '.$sageResponse['BatchNumber']);
            if ($isLiveApiCallStep1) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep2 = true;
            $readyToPostInvoiceAr = self::readyToPostARPremAndCommInvoicePayload($sageResponse['BatchNumber'], $isReversal);

            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep2 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                LoggerService::info('Reversal of EP AR Invoice Premium and Commission ready to post already sent', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
            } else {
                LoggerService::info('Sending Reversal of EP AR Invoice Premium and Commission ready to post to Sage', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making Reversal EP Ar invoice & prem ready to post to sage';
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post Reversal EP AR Invoice Premium and Commission Ready To Post batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value'],
                    ]);

                    $arInvoiceBatch = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    LoggerService::info('Checking status of Reversal EP AR Invoice Premium and Commission batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found',
                    ]);

                    if (isset($arInvoiceBatch['BatchStatus']) && $arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info('Reversal of EP AR Invoice Premium and Commission batch already posted', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, '', $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($arInvoiceBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get Reversal EP AR Invoice Premium and Commission batch status', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = ' EP code: '.$embeddedTransaction->code.' : Failed to get status of Reversal AR Invoice Premium and Commission batch - '.$sageResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    } else {
                        LoggerService::info('Error while making Reversal EP AR Invoice Premium and Commission ready to post to sage', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = ' EP code: '.$embeddedTransaction->code.' : Failed to post Reversal AR Invoice Premium and Commission Ready To Post batch - '.$sageResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    }
                } else {
                    if ($isLiveApiCallStep2) {
                        LoggerService::info('Logging Reversal EP AR Invoice Premium and Commission Ready To Post batch successfully', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }
            } else {
                if ($isLiveApiCallStep2) {
                    LoggerService::info('Logging Reversal EP AR Invoice Premium and Commission Ready To Post batch successfully with empty response', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            $isLiveApiCallStep3 = true;
            $aRPostInvoices = self::postARPremAndCommInvoicePayload($sageResponse['BatchNumber'], $isReversal);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('Reversal of EP AR Invoice Premium and Commission AR Post already sent');
                $isLiveApiCallStep3 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                if (($isAlreadyPosted && isset($aRPostInvoices)) || (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL)) {
                    if ($isAlreadyPosted) {
                        LoggerService::info('Reversal of EP AR Invoice Premium and Commission batch already posted', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $postedResponse = $aRPostInvoices['payload'];
                    } else {
                        LoggerService::info('Checking status of Reversal EP AR Invoice Premium and Commission batch', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $arInvoiceBatch = $this->sageApiService->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                        $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                        LoggerService::info('Status of Reversal EP AR Invoice Premium and Commission batch', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found',
                        ]);

                        if (! isset($arInvoiceBatch['BatchStatus'])) {
                            $message = ' EP code: '.$embeddedTransaction->code.' : Reversal - AR Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;

                            return $returnMessage;
                        }

                        if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('Reversal of EP AR Invoice Premium and Commission batch already posted', extra : [
                                'BatchNumber' => $sageResponse['BatchNumber'],
                            ]);
                            $postedResponse = $aRPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info('Sending Reversal of EP AR Invoice Premium and Commission AR Post to Sage');
                    $resp = $this->sageApiService->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = ' EP code: '.$embeddedTransaction->code.' : Error while making Ar invoice & prem Posted to sage';
                $message = ' EP code: '.$embeddedTransaction->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            }
            LoggerService::info('Reversal of EP AR Invoice Premium and Commission AR Post completed successfully', extra : [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        } else {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.' : Ar invoice & prem failed from sage';
            $message = ' EP code: '.$embeddedTransaction->code.' : createARInvoicePremAndComm  failed';

            return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        LoggerService::info('Completed Reversal of EP AR Invoice Premium and Commission creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Reversal of AR Premium and Commission invoice created on sage for SendUpdate Code : '.$sendUpdateLog->code.' EP code: '.$embeddedTransaction->code;

        return $returnMessage;

    }

    private function createAPPremInvoiceReversal($sageRequestDataArray, $isReversal = false)
    {
        [$sendUpdateLog, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 30;
        $stepsMapping = ['step_1' => 28, 'step_2' => 29, 'step_3' => 30];
        $isAlreadyPosted = false;

        LoggerService::info('Starting Reversal of EP AP Invoice Premium creation', extra: [
            'SendUpdateCode' => $sendUpdateLog->code,
        ]);

        $isLiveApiCallStep28 = true;
        $createAPInvoicePrem = self::createAPPremInvoicePayload($sageRequest, $sageRequestEmbeddedProduct, $isReversal);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Reversal of EP AP Invoice Premium already sent', extra: [
                'SendUpdateCode' => $sendUpdateLog->code,
            ]);
            $isLiveApiCallStep28 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending Reversal of EP AP Invoice Premium to Sage', extra: [
                'SendUpdateCode' => $sendUpdateLog->code,
            ]);
            $resp = $this->sageApiService->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            LoggerService::info('Reversal of EP AP Invoice Premium batch number - '.$postedResponse['BatchNumber'], extra: [
                'SendUpdateCode' => $sendUpdateLog->code,
            ]);
            if ($isLiveApiCallStep28) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }

            $isLiveApiCallStep29 = true;
            $readyToPostInvoiceAP = self::readyToPostAPPremInvoicePayload($postedResponse['BatchNumber'], $isReversal);

            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep29 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                LoggerService::info('Reversal of EP AP Invoice Premium ready to post already sent', extra: [
                    'SendUpdateCode' => $sendUpdateLog->code,
                    'BatchNumber' => $postedResponse['BatchNumber'],
                ]);
            } else {
                LoggerService::info('Sending Reversal of EP AP Invoice Premium ready to post to Sage', extra: [
                    'SendUpdateCode' => $sendUpdateLog->code,
                    'BatchNumber' => $postedResponse['BatchNumber'],
                ]);
                $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making Reversal EP AP invoice ready to post to sage';
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post Reversal EP AP Invoice Premium Ready To Post batch', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value'],
                        'SendUpdateCode' => $sendUpdateLog->code,
                    ]);

                    $aPInvoiceBatch = $this->sageApiService->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    LoggerService::info('Checking status of Reversal EP AP Invoice Premium batch', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                        'BatchStatus' => $aPInvoiceBatch['BatchStatus'] ?? 'Not found',
                        'SendUpdateCode' => $sendUpdateLog->code,
                    ]);

                    if (isset($aPInvoiceBatch['BatchStatus']) && $aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostInvoiceAP, '', $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aPInvoiceBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get Reversal EP AP Invoice Premium batch status', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                            'SendUpdateCode' => $sendUpdateLog->code,
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' : Failed to get status of AP Invoice Premium batch - '.$postedResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    } else {
                        LoggerService::info('Error while making Reversal EP AP Invoice Premium ready to post to sage', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                            'SendUpdateCode' => $sendUpdateLog->code,
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId);
                        $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' : Failed to post AP Invoice Premium Ready To Post batch - '.$postedResponse['BatchNumber'].' failed';

                        return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                    }
                } else {
                    if ($isLiveApiCallStep29) {
                        LoggerService::info('Logging Reversal EP AP Invoice Premium Ready To Post batch successfully', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                            'SendUpdateCode' => $sendUpdateLog->code,
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                    }
                }
            } else {
                if ($isLiveApiCallStep29) {
                    LoggerService::info('Logging Reversal EP AP Invoice Premium Ready To Post batch successfully with empty response', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                        'SendUpdateCode' => $sendUpdateLog->code,
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }

            $apBatchNumber = $postedResponse['BatchNumber'];
            $isLiveApiCallStep30 = true;
            $aPPostInvoices = self::postAPPremInvoicePayload($apBatchNumber, $isReversal);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('Reversal of EP AP Invoice Premium already posted', extra: [
                    'SendUpdateCode' => $sendUpdateLog->code,
                ]);
                $isLiveApiCallStep30 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                if (($isAlreadyPosted && isset($aPPostInvoices)) || (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL)) {
                    if ($isAlreadyPosted) {
                        LoggerService::info('Reversal of EP AP Invoice Premium batch already posted', extra : [
                            'BatchNumber' => $apBatchNumber,
                            'SendUpdateCode' => $sendUpdateLog->code,
                        ]);
                        $postedResponse = $aPPostInvoices['payload'];
                    } else {
                        LoggerService::info('Checking status of Reversal EP AP Invoice Premium batch', extra : [
                            'BatchNumber' => $apBatchNumber,
                            'SendUpdateCode' => $sendUpdateLog->code,
                        ]);
                        $aPInvoiceBatch = $this->sageApiService->postToSage300('AP/APInvoiceBatches('.$apBatchNumber.')', [], 'GET');
                        $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                        LoggerService::info('Status of Reversal EP AP Invoice Premium batch', extra: [
                            'BatchNumber' => $apBatchNumber,
                            'BatchStatus' => $aPInvoiceBatch['BatchStatus'] ?? 'Not found',
                            'SendUpdateCode' => $sendUpdateLog->code,
                        ]);

                        if (! isset($aPInvoiceBatch['BatchStatus'])) {
                            $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' : Reversal - AP Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;

                            return $returnMessage;
                        }

                        if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('Reversal of EP AP Invoice Premium batch already posted', extra : [
                                'BatchNumber' => $apBatchNumber,
                                'SendUpdateCode' => $sendUpdateLog->code,
                            ]);
                            $postedResponse = $aPPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info('Sending Reversal of EP AP Invoice Premium AP Post to Sage', extra: [
                        'SendUpdateCode' => $sendUpdateLog->code,
                    ]);
                    $resp = $this->sageApiService->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' Error while making AP invoices Posted to sage';
                $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' aPPostInvoices failed';

                return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
            } else {
                LoggerService::info('Reversal of EP AP Invoice Premium AP Post completed successfully', extra : [
                    'BatchNumber' => $apBatchNumber,
                    'SendUpdateCode' => $sendUpdateLog->code,
                ]);
                if ($isLiveApiCallStep30) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $sendUpdateLog, $sendUpdateLog, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                }
            }
        } else {
            $errorMessage = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' : Ap invoice prem failed from sage';
            $message = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' : createAPInvoicePrem  failed';

            return $this->sageApiService->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        LoggerService::info('Completed Reversal of EP AP Invoice Premium creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'SendUpdate Code : '.$sendUpdateLog->code.' Reversal of EP code: '.$embeddedTransaction->code.' AP Premium invoice created on sage';

        return $returnMessage;
    }

    public function applyPaymentARInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$quote, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        LoggerService::info('Starting EP Apply Payment AR Invoices', extra: [
            'QuoteCode' => $quote->code,
        ]);
        $totalSteps = 12;
        $currentStep = 10;
        $isLiveApiCallStep10 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('EP Apply Payment Receipt already sent', extra: [
                'QuoteCode' => $quote->code,
            ]);
            $isLiveApiCallStep10 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending EP Apply Payment Receipt to Sage', extra: [
                'QuoteCode' => $quote->code,
            ]);
            $payLoadOptions = self::createApplyPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct);
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep10) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.' Error while making split prepayments to sage';
            $message = ' EP code: '.$embeddedTransaction->code.' createPaymentReceiptOneInvoice failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        LoggerService::info('EP Apply Payment Receipt batch number - '.$batchNumber, extra: [
            'QuoteCode' => $quote->code,
        ]);

        $currentStep = 11;
        $isLiveApiCallStep11 = true;
        $readyToPostReceiptAr = self::readyToPostApplyPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('EP Apply Payment Receipt ready to post already sent', extra: [
                'QuoteCode' => $quote->code,
            ]);
            $isLiveApiCallStep11 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending EP Apply Payment Receipt ready to post to Sage', extra: [
                'QuoteCode' => $quote->code,
            ]);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.'  : Error while making Apply payment ready to post to sage';
            $message = ' EP code: '.$embeddedTransaction->code.'  : readyToPostReceiptAr failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        } else {
            LoggerService::info('EP Apply Payment Receipt ready to post completed successfully', extra: [
                'BatchNumber' => $batchNumber,
                'QuoteCode' => $quote->code,
            ]);
            if ($isLiveApiCallStep11) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        }

        $currentStep = 12;
        $isLiveApiCallStep12 = true;
        $aRPostReceipts = self::postApplyPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('EP Apply Payment AR Post already sent', extra: [
                'QuoteCode' => $quote->code,
            ]);
            $isLiveApiCallStep12 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('Checking status of EP Apply Payment AR Receipts batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'QuoteCode' => $quote->code,
                ]);
                $aRReceiptBatch = $this->sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                LoggerService::info('Status of EP Apply Payment AR Receipts batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'BatchStatus' => $aRReceiptBatch['BatchStatus'] ?? 'Not found',
                    'QuoteCode' => $quote->code,
                ]);
                if (! isset($aRReceiptBatch['BatchStatus'])) {
                    $message = ' EP code: '.$embeddedTransaction->code.'  :Apply Upfront Payment batch status key not defined';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('EP Apply Payment AR Receipts batch already posted', extra: [
                        'BatchNumber' => $batchNumber,
                        'QuoteCode' => $quote->code,
                    ]);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('Sending EP Apply Payment AR Post to Sage', extra: [
                    'QuoteCode' => $quote->code,
                ]);
                $resp = $this->sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.'  : Error while making Apply payment Posted to sage';
            $message = ' EP code: '.$embeddedTransaction->code.'  :aRPostReceipts failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }
        LoggerService::info('EP Apply Payment AR Post completed successfully', extra: [
            'BatchNumber' => $batchNumber,
            'QuoteCode' => $quote->code,
        ]);
        if ($isLiveApiCallStep12) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }
        LoggerService::info('Completed EP Apply Payment AR Invoices successfully', extra: [
            'QuoteCode' => $quote->code,
        ]);
        $returnMessage['status'] = true;
        $returnMessage['message'] = ' EP code: '.$embeddedTransaction->code.' Prepayments applied on sage';

        return $returnMessage;

    }

    public static function createEmbeddedProductPayload($embeddedProductTransaction, $insurerRequestResponse, $epShortCode = null)
    {
        return match ($epShortCode) {
            EmbeddedProductEnum::ECB => self::createEmbeddedProductPayloadForECB($embeddedProductTransaction, $insurerRequestResponse),
            default => self::createEmbeddedProductPayloadSukoonMedXRedx($embeddedProductTransaction, $insurerRequestResponse),
        };
    }
    private static function createEmbeddedProductPayloadForECB($embeddedProductTransaction, $insurerRequestResponse)
    {
        $insuranceProvider = $insurerRequestResponse->insuranceProvider;
        $insurerRequestResponseObject = json_decode($insurerRequestResponse->response);

        $epRefCode = $embeddedProductTransaction->code;
        $policyNumber = $insurerRequestResponseObject->policy_no;

        $originalInsurerTaxInvoiceNumber = $insurerRequestResponseObject->premium_inv_no;
        $originalCommissionTaxInvoiceNumber = $insurerRequestResponseObject->commision_inv_no;

        $insurerTaxInvoiceNumber = $originalInsurerTaxInvoiceNumber;
        $commissionTaxInvoiceNumber = $originalCommissionTaxInvoiceNumber;

        $sageRequestEmbeddedProduct = new stdClass;
        $sageRequestEmbeddedProduct->tapChargeId = $embeddedProductTransaction?->payment->paymentSplits->first()?->paymentCharges?->transaction_id;
        $sageRequestEmbeddedProduct->epSageReceiptId = null;
        $sageRequestEmbeddedProduct->invoiceDescription = $epRefCode.'.'.$policyNumber;
        $sageRequestEmbeddedProduct->policyNumber = $policyNumber;
        $sageRequestEmbeddedProduct->sageVendorId = $insuranceProvider->sage_vendor_id;
        $sageRequestEmbeddedProduct->sageCustomerNumber = $insuranceProvider->sage_insurer_customer_id;
        $sageRequestEmbeddedProduct->sageInsurerGlLiabilityAccount = $insuranceProvider->gl_liaiblity_account;
        $sageRequestEmbeddedProduct->insurerName = $insuranceProvider->text;
        $sageRequestEmbeddedProduct->collectionAmount = $insurerRequestResponseObject->policy_premium_with_tax;
        $sageRequestEmbeddedProduct->commissionTaxInvoiceNumber = (string) mb_substr($commissionTaxInvoiceNumber, -18);
        $sageRequestEmbeddedProduct->originalCommissionTaxInvoiceNumber = $originalCommissionTaxInvoiceNumber;
        $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber = (string) mb_substr($insurerTaxInvoiceNumber, -18);
        $sageRequestEmbeddedProduct->originalInsurerTaxInvoiceNumber = $originalInsurerTaxInvoiceNumber;
        $sageRequestEmbeddedProduct->createdOn = Carbon::parse($insurerRequestResponseObject->premium_inv_dt)->format(env('DATE_FORMAT_ONLY'));
        $sageRequestEmbeddedProduct->taxAmount = $insurerRequestResponseObject->policy_premium_tax;
        $sageRequestEmbeddedProduct->policyPrice = $insurerRequestResponseObject->policy_premium_without_tax;
        $sageRequestEmbeddedProduct->totalPrice = $insurerRequestResponseObject->policy_premium_with_tax;
        $sageRequestEmbeddedProduct->paymentAmount = $insurerRequestResponseObject->policy_premium_with_tax;
        $sageRequestEmbeddedProduct->brokerCommissionAmount = $insurerRequestResponseObject->policy_commision_without_tax;
        $sageRequestEmbeddedProduct->brokerCommissionVatAmount = $insurerRequestResponseObject->policy_commision_tax;
        $sageRequestEmbeddedProduct->brokerCommissionTotalAmount = $insurerRequestResponseObject->policy_commision_with_tax;
        $sageRequestEmbeddedProduct->startDate = Carbon::parse($insurerRequestResponseObject->policy_start_dt)->format(env('DATE_FORMAT_ONLY'));
        $sageRequestEmbeddedProduct->endDate = Carbon::parse($insurerRequestResponseObject->policy_end_dt)->format(env('DATE_FORMAT_ONLY'));

        return $sageRequestEmbeddedProduct;
    }

    private static function createEmbeddedProductPayloadSukoonMedXRedx($embeddedProductTransaction, $insurerRequestResponse)
    {
        $insuranceProvider = $insurerRequestResponse->insuranceProvider;
        $insurerRequestResponseObject = json_decode($insurerRequestResponse->response);

        $epRefCode = $embeddedProductTransaction->code;
        $policyNumber = $insurerRequestResponseObject->policy_number;

        $originalInsurerTaxInvoiceNumber = $insurerRequestResponseObject->additional_data->tax_invoice_document_number;
        $originalCommissionTaxInvoiceNumber = $insurerRequestResponseObject->additional_data->tax_invoice_buyer_document_number;

        $insurerTaxInvoiceNumber = self::formatDocNumber($originalInsurerTaxInvoiceNumber);
        $commissionTaxInvoiceNumber = self::formatDocNumber($originalCommissionTaxInvoiceNumber);

        $sageRequestEmbeddedProduct = new stdClass;
        $sageRequestEmbeddedProduct->tapChargeId = $embeddedProductTransaction?->payment->paymentSplits->first()?->paymentCharges?->transaction_id;
        $sageRequestEmbeddedProduct->epSageReceiptId = null;
        $sageRequestEmbeddedProduct->invoiceDescription = $epRefCode.'.'.$policyNumber;
        $sageRequestEmbeddedProduct->policyNumber = $policyNumber;
        $sageRequestEmbeddedProduct->sageVendorId = $insuranceProvider->sage_vendor_id;
        $sageRequestEmbeddedProduct->sageCustomerNumber = $insuranceProvider->sage_insurer_customer_id;
        $sageRequestEmbeddedProduct->sageInsurerGlLiabilityAccount = $insuranceProvider->gl_liaiblity_account;
        $sageRequestEmbeddedProduct->insurerName = $insuranceProvider->text;
        $sageRequestEmbeddedProduct->collectionAmount = $insurerRequestResponseObject->payments[0]->amount;
        $sageRequestEmbeddedProduct->commissionTaxInvoiceNumber = (string) mb_substr($commissionTaxInvoiceNumber, -18);
        $sageRequestEmbeddedProduct->originalCommissionTaxInvoiceNumber = $originalCommissionTaxInvoiceNumber;
        $sageRequestEmbeddedProduct->insurerTaxInvoiceNumber = (string) mb_substr($insurerTaxInvoiceNumber, -18);
        $sageRequestEmbeddedProduct->originalInsurerTaxInvoiceNumber = $originalInsurerTaxInvoiceNumber;
        $sageRequestEmbeddedProduct->createdOn = Carbon::createFromFormat('d/m/Y', $insurerRequestResponseObject->created_on)->format(env('DATE_FORMAT_ONLY'));
        $sageRequestEmbeddedProduct->taxAmount = $insurerRequestResponseObject->pricing->tax_amount;
        $sageRequestEmbeddedProduct->policyPrice = $insurerRequestResponseObject->pricing->policy_price;
        $sageRequestEmbeddedProduct->totalPrice = $insurerRequestResponseObject->pricing->total_price;
        $sageRequestEmbeddedProduct->paymentAmount = $insurerRequestResponseObject->payments[0]->amount;
        $sageRequestEmbeddedProduct->brokerCommissionAmount = $insurerRequestResponseObject->additional_data->broker_commission_amount;
        $sageRequestEmbeddedProduct->brokerCommissionVatAmount = $insurerRequestResponseObject->additional_data->broker_commission_vat_amount;
        $sageRequestEmbeddedProduct->brokerCommissionTotalAmount = $insurerRequestResponseObject->additional_data->broker_commission_total_amount;
        $sageRequestEmbeddedProduct->startDate = Carbon::createFromFormat('d/m/Y', $insurerRequestResponseObject->start_date)->format(env('DATE_FORMAT_ONLY'));
        $sageRequestEmbeddedProduct->endDate = Carbon::createFromFormat('d/m/Y', $insurerRequestResponseObject->end_date)->format(env('DATE_FORMAT_ONLY'));

        return $sageRequestEmbeddedProduct;

    }

    private static function createARPaymentReceiptsPayload($sageRequest, $sageRequestEmbeddedProduct)
    {
        $optionalFields = self::createEPPrepaymentOptionalFields($sageRequest, $sageRequestEmbeddedProduct);

        $entryType = SageEnum::SCT_STRAIGHT;
        $customerNumber = $sageRequest->customerId;
        $bankCode = SageEnum::BANK_CODE_INS;
        $paymentCode = SageEnum::PAYMENT_CODE_IP;
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
                'Value' => (string) $sageRequestEmbeddedProduct->totalPrice,
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
        $commissionTaxClass = 2; // if commission vat not applicable added
        if ($sageRequestEmbeddedProduct->brokerCommissionVatAmount > 0) {
            $commissionTaxClass = 1; // if commission vat applicable added
        }
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
                    'TaxClass1' => $commissionTaxClass,
                    'DocumentTotalBeforeTax' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionAmount),
                    'DocumentTotalIncludingTax' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionAmount),
                    'PostingDate' => Carbon::parse($request->bookingDate)->format(SagePayloadFactory::instanceData()->sage_api_date_format),
                    'InvoiceDetails' => [
                        [
                            'Description' => $commissionDescription,
                            'TaxClass1' => $commissionTaxClass,
                            'RevenueAccount' => '60010',
                            'ExtendedAmountWithTIP' => roundNumber($sageRequestEmbeddedProduct->brokerCommissionAmount),
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
            'BankCode' => SageEnum::BANK_CODE_INS,
            'ReceiptsAdjustments' => [
                [
                    'BatchType' => 'CA',
                    'CustomerNumber' => $sageRequest->customerId,
                    'BankCode' => SageEnum::BANK_CODE_INS,
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
        LoggerService::info($logFor.' Updating Embedded Product Sage Booking Status', extra: [
            'EmbeddedTransactionCode' => $embeddedTransaction->code,
            'Status' => $status,
            'CurrentStatus' => $embeddedTransaction->sage_status_id,
        ]);
        if ($embeddedTransaction->sage_status_id != $status) {
            $embeddedTransaction->update(['sage_status_id' => $status]);
            LoggerService::info($logFor.' Embedded Product Sage Booking Status Updated to : '.$status);
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
        $bankCode = SageEnum::BANK_CODE_INS;
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
        [$quote, $embeddedTransaction, $sageRequest, $sageRequestEmbeddedProduct, $sageLogArray] = $sageRequestDataArray;
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  Start applyPaymentAPInvoices for : '.$quote->code.' ');

        $totalSteps = 15;
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  : createAPPaymentReceiptOneInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep13 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.' :  Send createAPPaymentReceiptOneInvoice  for '.$quote->code);
            $payLoadOptions = self::createApplyAPPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct);
            $resp = $this->sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.' Error while making split prepayments to sage';
            $message = ' EP code: '.$embeddedTransaction->code.' createAPPaymentReceiptOneInvoice failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  : '.$quote->code.' : createAPPaymentReceiptOneInvoice  - BatchNumber : '.$batchNumber.' completed successfully');

        // Step 14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        $readyToPostReceiptAP = self::readyToPostApplyAPPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  readyToPostReceiptAP  Sent Already for '.$quote->code);
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  Send readyToPostReceiptAP  for '.$quote->code);
            $readyToPostResponse = $this->sageApiService->postToSage300($readyToPostReceiptAP['endPoint'], $readyToPostReceiptAP['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = ' EP code: '.$embeddedTransaction->code.'  : Error while making Apply AP payment ready to post to sage';
            $message = ' EP code: '.$embeddedTransaction->code.'  : readyToPostReceiptAP failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $readyToPostReceiptAP, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        } else {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  : '.$quote->code.' : readyToPostReceiptAP completed successfully');
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAP, $readyToPostResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
            }
        }

        // delay is added because we are experiencing an error while posting AP Mapping
        sleep(3);

        // Step 15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        $aPPostReceipts = self::postApplyAPPrepaymentPayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  aPPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  Check status of  AP Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aPReceiptBatch = $this->sageApiService->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$batchNumber.')', [], 'GET');
                $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  Status of  AP Prepayment Receipts batch('.$batchNumber.') : ', extra: $aPReceiptBatch);
                if (! isset($aPReceiptBatch['BatchStatus'])) {
                    $message = ' EP code: '.$embeddedTransaction->code.'  :Apply AP Upfront Payment batch status key not defined';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aPPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  :  Send aPPostReceipts  for '.$quote->code);
                $resp = $this->sageApiService->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        $sageErrorMessageOnSuccess = $postedResponse['Message'] ?? null;
        $isErrorOccurred = $sageErrorMessageOnSuccess && str_contains($sageErrorMessageOnSuccess, SageEnum::SAGE_ERROR_OCCURRED_MESSAGE);
        if (isset($postedResponse['error']) || $isErrorOccurred) {
            $errorMessage = $isErrorOccurred ? $sageErrorMessageOnSuccess : ' EP code: '.$embeddedTransaction->code.'  : Error while making Apply AP payment Posted to sage';
            $message = $isErrorOccurred ? $sageErrorMessageOnSuccess : ' EP code: '.$embeddedTransaction->code.'  :aPPostReceipts failed';

            return $this->sageApiService->logErrorAndReturn([$embeddedTransaction, $message, $errorMessage, $aPPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL, $sageRequest->userId]);
        }
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  : '.$quote->code.' : aPPostReceipts completed successfully');
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aPPostReceipts, $postedResponse, $embeddedTransaction, $quote, $currentStep, $totalSteps, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
        }
        LoggerService::info(self::CLASSNAME.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' EP code: '.$embeddedTransaction->code.'  : End applyPaymentAPInvoices for : '.$quote->code.' ');
        $returnMessage['status'] = true;
        $returnMessage['message'] = ' EP code: '.$embeddedTransaction->code.' AP Prepayments applied on sage';

        return $returnMessage;
    }

    public static function createApplyAPPrepaymentPayload($sageRequest, $sageRequestEmbeddedProduct)
    {
        $entryType = SageEnum::SCT_STRAIGHT;
        $payLoad = [
            'BatchSelector' => 'PY',
            'Description' => 'CLIENT PAYMENT MAPPING',
            'BankCode' => SageEnum::BANK_CODE_INS,
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
