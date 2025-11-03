<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PaymentChargesEnum;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentGatewayEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Http\Requests\SplitPaymentApproveRequest;
use App\Jobs\BookEmbeddedProductOnSageJob;
use App\Jobs\BookPolicyOnSageJob;
use App\Jobs\PostPrepaymentToSageJob;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Jobs\SendUpdateSageJob;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatusLog;
use App\Models\QuoteTag;
use App\Models\SageApiLog;
use App\Models\SageProcess;
use App\Models\SendUpdateLog;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SageApiLogRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\HandlesDeadlockRetries;
use App\Traits\SageLoggable;
use App\Traits\TeamHierarchyTrait;
use Cache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class SageApiService
{
    use GenericQueriesAllLobs;
    use HandlesDeadlockRetries;
    use SageLoggable;
    use TeamHierarchyTrait;

    protected $sageLogin;
    protected $sagePassword;
    protected $sageRequestUrl;
    protected $sageBatchNumber;
    protected $sageDBName;
    protected $recursiveCallStatus;

    public function __construct()
    {
        // Guzzle was not working for post request
        $this->sageLogin = env('SAGE_300_LOGIN');
        $this->sagePassword = env('SAGE_300_PASSWORD');
        $this->sageRequestUrl = env('SAGE_300_BASE_URL').env('SAGE_300_VERSION');
        $this->sageDBName = env('SAGE_300_CUSTOM_API_DB_NAME');
        $this->sageBatchNumber = '';
        $this->recursiveCallStatus = SageEnum::STATUS_SUCCESS;
    }

    public function verifySageCustomer($customerId, $data = null, $logModal = null, $totalSteps = 4, $authUserId = null)
    {
        LoggerService::info('Starting Sage customer verification', extra: [
            'QuoteType' => $data['quoteTypeId'],
            'RefId' => $data['id'],
        ]);

        $customer = Customer::find($customerId);
        $quoteEntityMapping = QuoteRequestEntityMapping::with('entity')->where(['quote_type_id' => $data['quoteTypeId'], 'quote_request_id' => $data['id']])->first();
        $quoteEntity = $quoteEntityMapping?->entity;
        $payLoadOptions = SagePayloadFactory::createCustomerPayload($customer, $quoteEntity);
        $sageCustomerFromDB = ($quoteEntity) ? $quoteEntity?->sage_customer_number : $customer?->sage_customer_number;
        $sageCustomerNumber = false;
        $customerNumber = $sageCustomerFromDB ?? $payLoadOptions['customerNumber'];
        $urlGetCustomer = SageEnum::END_POINT_AR_CUSTOMER."('".$customerNumber."')";
        $customerResponse = json_decode($this->postToSage300($urlGetCustomer, [], 'GET'), true);

        if ($customerResponse && isset($customerResponse['CustomerNumber'])) {
            LoggerService::info('Customer already exists in Sage', extra: [
                'SageCustomerNumber' => $customerResponse['CustomerNumber'],
            ]);

            $sageCustomerNumber = $customerResponse['CustomerNumber'];
            $this->logSageApiCall([
                'endPoint' => SageEnum::END_POINT_AR_CUSTOMER,
                'payload' => $sageCustomerFromDB ? [] : $payLoadOptions,
                'customerNumber' => $sageCustomerNumber,
                'sage_request_type' => SageEnum::SRT_GET_CUSTOMER,
                'entry_type' => SageEnum::SCT_STRAIGHT,
            ], '', $logModal, $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $authUserId);
        }

        $responseError = isset($customerResponse['error']['code']) ? $customerResponse['error']['code'] : false;
        if ($responseError && $responseError == SageEnum::ERROR_RECORD_NOT_FOUND) {
            LoggerService::info('Customer not found in Sage. Proceeding to create customer via Sage API');
            $customerResponse = json_decode($this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']), true);

            if ($customerResponse && isset($customerResponse['CustomerNumber'])) {
                LoggerService::info('Customer successfully created in Sage', extra : [
                    'SageCustomerNumber' => $customerResponse['CustomerNumber'],
                ]);
                $sageCustomerNumber = $customerResponse['CustomerNumber'];
                $this->logSageApiCall($payLoadOptions, $customerResponse, $logModal, $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $authUserId);
            } else {
                LoggerService::info('Error creating customer in Sage', extra : [
                    'Payload' => json_encode($payLoadOptions['payload']),
                    'Response' => json_encode($customerResponse),
                ]);
                $this->logSageApiCall($payLoadOptions, $customerResponse, $logModal, $logModal, 1, $totalSteps, SageEnum::STATUS_FAIL, $authUserId);
            }
        }

        if (! $sageCustomerFromDB && ($customerResponse && isset($customerResponse['CustomerNumber']))) {
            if ($quoteEntity) {
                $quoteEntity->sage_customer_number = $sageCustomerNumber;
                $quoteEntity->save();
                LoggerService::info('Sage customer code updated in entities table', extra : [
                    'SageCustomerNumber' => $sageCustomerNumber,
                ]);
            } elseif ($customer) {
                $customer->sage_customer_number = $sageCustomerNumber;
                $customer->save();
                LoggerService::info('Sage customer code updated in customers table', extra : [
                    'SageCustomerNumber' => $sageCustomerNumber,
                ]);
            }
        }

        return $sageCustomerNumber;
    }

    public function postToSage300($endPoint, $payLoad, $verb = 'POST')
    {
        $sageEndPoint = $this->sageRequestUrl.$endPoint;
        $verb = strtoupper($verb);
        try {
            $response = match ($verb) {
                'PATCH' => Http::timeout(80)->withBasicAuth($this->sageLogin, $this->sagePassword)
                    ->patch($sageEndPoint, $payLoad),
                'POST' => Http::timeout(80)->withBasicAuth($this->sageLogin, $this->sagePassword)
                    ->post($sageEndPoint, $payLoad),
                default => Http::timeout(80)->withBasicAuth($this->sageLogin, $this->sagePassword)
                    ->get($sageEndPoint, $payLoad),
            };

            if ($response->failed()) {
                $responseBody = is_array($response->json()) ? $response->json() : json_decode($response->body(), true);
                $errorMessage = data_get($responseBody, 'error.message.value', 'Something went wrong with Sage Server.');
                LoggerService::info(self::class.' fn: '.__FUNCTION__." - Sage API request failed - Endpoint: {$endPoint} - Error: {$errorMessage}", extra: [
                    'response' => $responseBody,
                    'payLoad' => $payLoad,
                ]);
            }

            return is_array($response->json()) ? json_encode($response->json()) : $response->body();
        } catch (Exception $e) {
            $errorMessage = strtolower($e->getMessage());
            if (str_contains($errorMessage, SageEnum::SAGE_EMPTY_RESPONSE_MESSAGE)) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Sage API empty response - Endpoint: '.$endPoint.' - Error: '.$errorMessage);
            } else {
                LoggerService::error(self::class.' fn: '.__FUNCTION__.' - Sage API exception - Endpoint: '.$endPoint.' - Error: '.$errorMessage);
            }

            return json_encode(['error' => ['message' => ['value' => $errorMessage]], 'code' => 500]);
        }
    }

    public function sendUpdateSageLogs($sendUpdateRequest, $sendUpdateLog): array
    {
        $sageLogsArray = $reversalInvoiceLogs = [];
        $sendUpdateCategory = $sendUpdateLog?->category?->code;
        if (in_array($sendUpdateCategory, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Fetching logs for non CPD Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
            $sageLogsArray = $sendUpdateLog->sageApiLogs?->whereNotIn('entry_type', [
                SageEnum::SRT_GET_AR_INVOICE,
                SageEnum::SRT_GET_AP_INVOICE,
                SageEnum::SCT_REVERSAL,
                SageEnum::SCT_CORRECTION,
            ])->keyBy('step')->toArray();
        }

        if ($sendUpdateCategory == SendUpdateLogStatusEnum::CPD) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Fetching logs for CPD Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

            $getReverseInvoiceRelation = [];
            $quoteModelObject = $this->getModelObject($sendUpdateRequest->quoteType);
            $quoteDetails = $quoteModelObject::where('id', $sendUpdateRequest->quoteRefId)->first();
            $sageLogsArray = $sendUpdateLog->sageApiLogs?->keyBy('step')->toArray();
            $getPaymentByInsurerInvoiceNumber = PaymentRepository::getPaymentByInsurerInvoiceNumber($quoteDetails, $sendUpdateRequest->reversalInvoice);
            $isReversalInvoiceEndorsement = app(SendUpdateLogService::class)->isReversalInvoiceEndorsement($sendUpdateRequest->reversalInvoice);

            if ($getPaymentByInsurerInvoiceNumber && $getPaymentByInsurerInvoiceNumber->send_update_log_id == null) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Getting Relation for Sage API Logs - Reversal Invoice is from Main Lead - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
                $getReverseInvoiceRelation = [
                    'section_type' => $getPaymentByInsurerInvoiceNumber->paymentable_type,
                    'section_id' => $getPaymentByInsurerInvoiceNumber->paymentable_id,
                ];
            } else {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Getting Relation for Sage API Logs - Reversal Invoice is Endorsement itself - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
                if ($isReversalInvoiceEndorsement !== null) {
                    $getReverseInvoiceRelation = [
                        'section_type' => $sendUpdateLog->getMorphClass(),
                        'section_id' => $isReversalInvoiceEndorsement->id,
                    ];
                }
            }

            if (empty($getReverseInvoiceRelation)) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Reversal invoice logs not found for reverse and correction - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

                return [$sageLogsArray, $reversalInvoiceLogs];
            }

            $getReverseInvoicesLogs = SageApiLog::where($getReverseInvoiceRelation)
                ->whereNotIn('entry_type', [
                    SageEnum::SRT_GET_AR_INVOICE,
                    SageEnum::SRT_GET_AP_INVOICE,
                    SageEnum::SCT_REVERSAL,
                ])->orderBy('step')->get()->toArray();

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Fetching reversal invoice logs for reverse and correction - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
            $reversalInvoiceLogs = collect($getReverseInvoicesLogs)->filter(function ($sageApiLog) {
                return in_array($sageApiLog['sage_request_type'], [
                    SageEnum::SRT_CREATE_AR_PREM_COMM_INV,
                    SageEnum::SRT_CREATE_AR_SPPAY_INV,
                    SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV,
                    SageEnum::SRT_CREATE_AR_SPPAY_CORR_INV,
                    SageEnum::SRT_CREATE_AP_PREM_INV,
                    SageEnum::SRT_CREATE_AP_SPPAY_INV,
                    SageEnum::SRT_CREATE_AP_PREM_CORR_INV,
                    SageEnum::SRT_CREATE_AP_SPPAY_CORR_INV,
                    SageEnum::SRT_CREATE_AR_DISC_INV,
                    SageEnum::SRT_CREATE_AR_DISC_CORR_INV,
                ]) && $sageApiLog['status'] == SageEnum::STATUS_SUCCESS;
            })->values()->toArray();

            if (empty($reversalInvoiceLogs)) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Reversal invoice logs not found for reverse and correction - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
            }
        }

        return [$sageLogsArray, $reversalInvoiceLogs];
    }

    public function bookEndorsementOnSage($endorsementPreparedPayload)
    {
        [$request, $sendUpdateLog, $sageRequestPayload] = $endorsementPreparedPayload;
        $sendUpdateCategory = $sendUpdateLog?->category?->code;
        [$sageLogsArray, $reversalInvoiceLogs] = $this->sendUpdateSageLogs($request, $sendUpdateLog);

        $quoteModelObject = $this->getModelObject($request->quoteType);
        $mainQuote = $quoteModelObject::where('id', $request->quoteRefId)->first();
        $preparedData = (new SendUpdateLogService)->preparedDetailsForEndorsement($request, $mainQuote, $sendUpdateLog);

        $preparedData['quoteDetails'] = $quoteModelObject::where('id', $request->quoteRefId)->first();
        $preparedData['sendUpdateLog'] = $sendUpdateLog;

        $isEndorsementActionDisabled = app(SendUpdateLogService::class)->isEndorsementBookingActionDisabled($sendUpdateLog);
        if (! $isEndorsementActionDisabled) {

            // create AR Prepayment Premium Receipt
            if (! empty($preparedData['payment']?->send_update_log_id)) {
                $createPrepayment = $this->createARPrepaymentPremiumReceipts([$sageRequestPayload, $mainQuote, $preparedData['payment'], $preparedData['splitPayments']]);
                if (! $createPrepayment['status']) {
                    return $createPrepayment;
                }
            }

            // create AP Prepayment Premium Receipt
            /*$createPremiumPrepayment = $this->createAPPrepaymentPremiumReceipts([$sageRequestPayload, $sendUpdateLog, $preparedData['payment'], $preparedData['splitPayments']]);
            if (! $createPremiumPrepayment['status']) {
                return $createPremiumPrepayment;
            }*/

            if ($sendUpdateCategory == SendUpdateLogStatusEnum::CPD) {
                if (empty($reversalInvoiceLogs)) {
                    return ['status' => false, 'message' => 'Reversal invoice logs not found for reverse and correction'];
                }

                $response = $this->bookReversalEndorsementOnSage($request, $preparedData, $sageRequestPayload, $sageLogsArray, $reversalInvoiceLogs, $sendUpdateLog);
            } else {
                $response = $this->bookStraightEndorsementOnSage($preparedData, $sageRequestPayload, $sageLogsArray, $request);
            }

            if (! $response['status']) {
                return $response;
            }
        } else {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Endorsement booking invoice creation on Sage is disabled - QuoteType: '.$request->quoteType.' - QuoteUUID: '.$request->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
        }

        $response = app(SendUpdateLogService::class)->updatesMoveToLead([$request, $sendUpdateLog, $preparedData]);
        if (! $response['status']) {
            return $response;
        }

        return $response;
    }

    public function bookStraightEndorsementOnSage($preparedData, $sageRequestPayload, $sageLogsArray, $request): array
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Upfront Endorsement Booking Start on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Payment frequency: '.$preparedData['payment']->frequency.' - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

        $extraDetails = ['sage_request_type' => ($preparedData['payment']->frequency == PaymentFrequency::UPFRONT) ? SageEnum::SRT_CREATE_AR_PREM_COMM_INV : SageEnum::SRT_CREATE_AR_SPPAY_INV];
        $extraDetails['extras']['option_id'] = $preparedData['sendUpdateLog']?->option?->code ?? null;
        $extraDetails['userId'] = $sageRequestPayload->userId ?? null;

        if (isset($preparedData['mainLeadDetails'])) {
            $extraDetails['mainLeadDetails'] = $preparedData['mainLeadDetails'];
        }

        // Create AR Commission and Premium Invoice
        $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$sageRequestPayload, $preparedData['sendUpdateLog'], $preparedData['payment'], $preparedData['splitPayments'], $sageLogsArray, $extraDetails]);
        if (! $createARInvoicePremAndComm['status']) {
            return $createARInvoicePremAndComm;
        }

        // Create AP Premium Invoice
        $extraDetails['sage_request_type'] = ($preparedData['payment']->frequency == PaymentFrequency::UPFRONT) ? SageEnum::SRT_CREATE_AP_PREM_INV : SageEnum::SRT_CREATE_AP_SPPAY_INV;
        if (! in_array($preparedData['sendUpdateLog']?->option?->code, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATCRNB_RBB])) {
            $createAPInvoicePrem = $this->createAPInvoicePrem([$sageRequestPayload, $preparedData['sendUpdateLog'], $preparedData['payment'], $preparedData['splitPayments'], $sageLogsArray, $extraDetails]);
            if (! $createAPInvoicePrem['status']) {
                return $createAPInvoicePrem;
            }
        }

        // Create AR Discount Invoice
        $extraDetails['sage_request_type'] = SageEnum::SRT_CREATE_AR_DISC_INV;
        if ($sageRequestPayload->discount > 0 && ! in_array(($preparedData['sendUpdateLog']?->option?->code ?? ''), [
            SendUpdateLogStatusEnum::ATIB,
            SendUpdateLogStatusEnum::ACB,
            SendUpdateLogStatusEnum::ATCRNB,
            SendUpdateLogStatusEnum::ATCRNB_RBB,
        ])) {
            $extraDetails['paymentFrequency'] = $preparedData['payment']->frequency;
            $createARInvoiceDis = $this->createARInvoiceDis([$sageRequestPayload, $preparedData['sendUpdateLog'], $sageLogsArray, $extraDetails]);
            if (! $createARInvoiceDis['status']) {
                return $createARInvoiceDis;
            }
            unset($extraDetails['paymentFrequency']);
        }

        $categoryCode = $preparedData['sendUpdateLog']?->category?->code;
        $optionCode = $preparedData['sendUpdateLog']?->option?->code;
        $isSendUpdateTypeCancelled = $categoryCode == SendUpdateLogStatusEnum::CI || ($categoryCode == SendUpdateLogStatusEnum::EF && $optionCode == SendUpdateLogStatusEnum::MPC);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SendUpdateType Checks before triggering of Reversal Of EP Booking - Quote Code: '.$preparedData['sendUpdateLog']->code, extra : [
            'sendUpdateCode' => $preparedData['sendUpdateLog'],
            'categoryCode' => $categoryCode,
            'optionCode' => $optionCode,
            'isSendUpdateTypePolicyCancelled' => $isSendUpdateTypeCancelled,
        ]);
        if ($isSendUpdateTypeCancelled) {
            $quoteTypeId = $preparedData['sendUpdateLog']->quote_type_id;
            $quote = $this->getQuoteObjectBy($request->quoteType, $preparedData['sendUpdateLog']->quote_uuid, 'uuid');
            $isLobAllowedForEmbeddedProductBooking = $this->isLobAllowedForEmbeddedProductBooking($quoteTypeId);
            $isTapPaymentGateway = $preparedData['payment']->payment_gateway_id == PaymentGatewayEnum::PAYMENT_GATEWAY_TAP;
            $ePTransactions = $this->getEPTransactions($quote, $quoteTypeId) ?? [];

            foreach ($ePTransactions as $ePTransaction) {

                $epTransSageLogArray = $ePTransaction?->sageApiLogs?->whereIn('sage_request_type', [SageEnum::EP_SRT_CREATE_AR_PREM_COMM_INV, SageEnum::EP_SRT_CREATE_AP_PREM_INV])->keyBy('step')->toArray() ?? [];
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Reversal Of EP Booking checks - Quote Code: '.$quote->code, extra : [
                    'isLobAllowedForEmbeddedProductBooking' => $isLobAllowedForEmbeddedProductBooking,
                    'ePTransaction' => $ePTransaction?->code,
                    'isTapPaymentGateway' => $isTapPaymentGateway,
                    'epBookingLogCount' => count($epTransSageLogArray),
                    'suCustomerNumber' => $sageRequestPayload->customerId,
                ]);
                if ($isLobAllowedForEmbeddedProductBooking && $ePTransaction && count($epTransSageLogArray) > 0) {
                    $quoteSageRequest = app(SagePayloadFactory::class)->sagePayLoad($request->quoteType, $preparedData['payment'], $quote, $preparedData['splitPayments']);
                    $quoteSageRequest->quoteTypeId = $quoteTypeId;
                    $quoteSageRequest->userId = $sageRequestPayload->userId;
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Reversal Of EP Booking : Start Sage booking Process for : '.$quote->code.' , EP Transaction Code : '.$ePTransaction->code.' ##################################');
                    $embeddedProductSageBookingResponse = (new SageApiEmbeddedProductService)->bookReversalOfEmbeddedProductOnSage([$quote, $preparedData['sendUpdateLog'], $quoteSageRequest, $ePTransaction], $ePTransaction?->product?->embeddedProduct?->short_code);
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Reversal Of EP Booking : End Sage booking Process for : '.$quote->code.' , EP Transaction Code : '.$ePTransaction->code.' ##################################', extra : $embeddedProductSageBookingResponse);
                    if (! $embeddedProductSageBookingResponse['status']) {
                        return $embeddedProductSageBookingResponse;
                    }

                }
            }
        }

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Upfront Endorsement Booking Completed on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

        return ['status' => true, 'message' => 'Straight Forward Endorsement Booking Completed on Sage'];
    }

    public function bookReversalEndorsementOnSage($request, $preparedData, $sageRequestPayload, $sageLogsArray, $reversalInvoiceLogs, $sendUpdateLog): array
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Reversal and Correction Endorsement Booking Start on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Payment frequency: '.$preparedData['payment']->frequency);

        $isOnlyDiscountReversal = false;
        $isOnlyDiscount = false;
        $reverseSageRequestTypes = collect($reversalInvoiceLogs)->pluck('sage_request_type')->toArray();

        if (empty($reverseSageRequestTypes)) {
            return ['status' => false, 'message' => 'Reversal invoice logs not found'];
        }

        $invoiceTypeForCPD = [SageEnum::SCT_REVERSAL, SageEnum::SCT_CORRECTION];
        $arInvoiceTypesWithDiscount = [
            SageEnum::SRT_CREATE_AR_PREM_COMM_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_INV,
            SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_CORR_INV,
            SageEnum::SRT_CREATE_AR_DISC_INV,
            SageEnum::SRT_CREATE_AR_DISC_CORR_INV,
        ];

        $arDiscountInvoiceTypes = [SageEnum::SRT_CREATE_AR_DISC_INV, SageEnum::SRT_CREATE_AR_DISC_CORR_INV];

        $arInvoiceTypes = [
            SageEnum::SRT_CREATE_AR_PREM_COMM_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_INV,
            SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV,
            SageEnum::SRT_CREATE_AR_SPPAY_CORR_INV,
        ];

        $apInvoiceTypes = [
            SageEnum::SRT_CREATE_AP_PREM_INV,
            SageEnum::SRT_CREATE_AP_SPPAY_INV,
            SageEnum::SRT_CREATE_AP_PREM_CORR_INV,
            SageEnum::SRT_CREATE_AP_SPPAY_CORR_INV,
        ];

        $extraDetails = ['sage_request_type' => SageEnum::SRT_REV_CORR_AR_PREM_COMM_INV];
        $extraDetails['userId'] = $sageRequestPayload->userId ?? null;

        if (isset($preparedData['mainLeadDetails'])) {
            $extraDetails['mainLeadDetails'] = $preparedData['mainLeadDetails'];
        }

        if (! empty(array_intersect($arDiscountInvoiceTypes, $reverseSageRequestTypes))) {
            $isOnlyDiscountReversal = $sendUpdateLog && (int) $sendUpdateLog->discount == 0;
        } else {
            if ($sendUpdateLog->discount > 0) {
                $isOnlyDiscount = true;
            }
        }

        foreach ($reverseSageRequestTypes as $reverseSageRequestTypeKey => $reverseSageRequestType) {
            $invoiceType = in_array($reversalInvoiceLogs[$reverseSageRequestTypeKey]['sage_request_type'], $arInvoiceTypesWithDiscount) ?
                SageEnum::SRT_GET_AR_INVOICE : (in_array($reversalInvoiceLogs[$reverseSageRequestTypeKey]['sage_request_type'], $apInvoiceTypes) ? SageEnum::SRT_GET_AP_INVOICE : null);

            if (is_null($invoiceType)) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Invalid Invoice Type - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

                return ['status' => false, 'message' => 'Error while getting Invoice from Sage'];
            }

            $reversalInvoiceDetails = [
                'reversalInvoice' => $reversalInvoiceLogs[$reverseSageRequestTypeKey],
                'sectionType' => $reversalInvoiceLogs[$reverseSageRequestTypeKey]['section_type'],
                'sectionId' => $reversalInvoiceLogs[$reverseSageRequestTypeKey]['section_id'],
                'sageRequestType' => $reversalInvoiceLogs[$reverseSageRequestTypeKey]['sage_request_type'],
                'invoiceType' => $invoiceType,
            ];

            $extraDetails['paymentFrequency'] = $preparedData['payment']->frequency;
            $getReversalInvoiceDetails = $this->getInvoiceDetailsForReversal([$reversalInvoiceDetails, $sendUpdateLog, $reversalInvoiceLogs[$reverseSageRequestTypeKey]['sage_request_type'], $sageLogsArray, $extraDetails]);
            if (! $getReversalInvoiceDetails['status']) {
                return $getReversalInvoiceDetails;
            }

            foreach ($invoiceTypeForCPD as $cpdInvoiceType) {
                $extraDetails['sage_entry_type'] = $cpdInvoiceType;
                $extraDetails['sageReversalInvoice'] = json_encode($getReversalInvoiceDetails['response']);
                $extraDetails['reversalFrequency'] = ($cpdInvoiceType == SageEnum::SCT_REVERSAL) ? PaymentFrequency::UPFRONT : null;
                if (is_null($extraDetails['reversalFrequency'])) {
                    unset($extraDetails['reversalFrequency']);
                }

                if (in_array($reverseSageRequestType, $arInvoiceTypes)) {
                    // Create AR Commission and Premium Invoice (Reversal and Correction)
                    $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$sageRequestPayload, $preparedData['sendUpdateLog'], $preparedData['payment'], $preparedData['splitPayments'], $sageLogsArray, $extraDetails]);
                    if (! $createARInvoicePremAndComm['status']) {
                        return $createARInvoicePremAndComm;
                    }
                }

                if (in_array($reverseSageRequestType, $apInvoiceTypes)) {
                    // Create AP Premium Invoice (Reversal and Correction)
                    $createAPInvoicePrem = $this->createAPInvoicePrem([$sageRequestPayload, $preparedData['sendUpdateLog'], $preparedData['payment'], $preparedData['splitPayments'], $sageLogsArray, $extraDetails]);
                    if (! $createAPInvoicePrem['status']) {
                        return $createAPInvoicePrem;
                    }
                }

                if (in_array($reverseSageRequestType, $arDiscountInvoiceTypes)) {
                    if ($cpdInvoiceType == SageEnum::SCT_REVERSAL) {
                        // Create AR Discount Invoice (Reversal)
                        $extraDetails['is_reversal_discount'] = true;
                        $createARInvoiceDis = $this->createARInvoiceDis([$sageRequestPayload, $preparedData['sendUpdateLog'], $sageLogsArray, $extraDetails]);
                        if (! $createARInvoiceDis['status']) {
                            return $createARInvoiceDis;
                        }
                    }

                    if ($cpdInvoiceType == SageEnum::SCT_CORRECTION && ! $isOnlyDiscountReversal) {
                        // Create AR Discount Invoice (Correction)
                        $createARInvoiceDis = $this->createARInvoiceDis([$sageRequestPayload, $preparedData['sendUpdateLog'], $sageLogsArray, $extraDetails]);
                        if (! $createARInvoiceDis['status']) {
                            return $createARInvoiceDis;
                        }
                    }
                }
            }
        }

        if ($isOnlyDiscount) {
            // Create AR Discount Invoice
            $extraDetails['sage_entry_type'] = SageEnum::SCT_STRAIGHT;
            $extraDetails['sage_request_type'] = SageEnum::SRT_CREATE_AR_DISC_INV;
            $extraDetails['is_only_correction'] = true;
            unset($extraDetails['sageReversalInvoice']);
            $createARInvoiceDis = $this->createARInvoiceDis([$sageRequestPayload, $preparedData['sendUpdateLog'], $sageLogsArray, $extraDetails]);
            if (! $createARInvoiceDis['status']) {
                return $createARInvoiceDis;
            }
        }

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Reversal and Correction Endorsement Booking Completed on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

        return ['status' => true, 'message' => 'Non Up-front Endorsement Booking Completed on Sage'];
    }

    private function getInvoiceDetailsForReversal($reversalInvoiceDetails): array
    {
        [$reversalInvoiceDetails, $sendUpdateLog, $getRequestType, $sageLogArray, $extraDetails] = $reversalInvoiceDetails;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $userId = $extraDetails['userId'] ?? null;

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Getting Invoice from Sage process start - SendUpdateCode: '.$sendUpdateLog?->code);
        $isPaymentUpfront = $extraDetails['paymentFrequency'] == PaymentFrequency::UPFRONT;
        $arEntryTypes = [
            SageEnum::SRT_CREATE_AR_PREM_COMM_INV => ['step' => 2],
            SageEnum::SRT_CREATE_AR_SPPAY_INV => ['step' => 2],
            SageEnum::SRT_CREATE_AR_PREM_COMM_CORR_INV => ['step' => 2],
            SageEnum::SRT_CREATE_AR_SPPAY_CORR_INV => ['step' => 2],
            SageEnum::SRT_CREATE_AR_DISC_INV => ['step' => $isPaymentUpfront ? 16 : 18],
            SageEnum::SRT_CREATE_AR_DISC_CORR_INV => ['step' => $isPaymentUpfront ? 16 : 18],
        ];

        $apEntryType = [
            SageEnum::SRT_CREATE_AP_PREM_INV => ['step' => $isPaymentUpfront ? 9 : 10],
            SageEnum::SRT_CREATE_AP_SPPAY_INV => ['step' => $isPaymentUpfront ? 9 : 10],
            SageEnum::SRT_CREATE_AP_PREM_CORR_INV => ['step' => $isPaymentUpfront ? 9 : 10],
            SageEnum::SRT_CREATE_AP_SPPAY_CORR_INV => ['step' => $isPaymentUpfront ? 9 : 10],
        ];

        $sageRequestType = $sageEntryType =
            (in_array($getRequestType, array_keys($arEntryTypes)) ? SageEnum::SRT_GET_AR_INVOICE : (in_array($getRequestType, array_keys($apEntryType)) ? SageEnum::SRT_GET_AP_INVOICE : null));

        if (is_null($sageRequestType)) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Invalid Invoice Type - SendUpdateCode: '.$sendUpdateLog?->code);

            return ['status' => false, 'message' => 'Error while getting Invoice from Sage'];
        }

        $step = ($sageRequestType == SageEnum::SRT_GET_AR_INVOICE ? $arEntryTypes[$getRequestType]['step'] ?? null : $apEntryType[$getRequestType]['step'] ?? null);

        $sageInvResponse = SageApiLogRepository::getInvoiceResponse([
            'quoteTypeObject' => $reversalInvoiceDetails['sectionType'],
            'quoteTypeId' => $reversalInvoiceDetails['sectionId'],
            'invoiceType' => $reversalInvoiceDetails['sageRequestType'],
        ]);

        $reverseInvoiceBatchNumber = json_decode($reversalInvoiceDetails['reversalInvoice']['response'])->BatchNumber;
        $payLoadOptions = SagePayloadFactory::getInvoiceDetails($reversalInvoiceDetails['invoiceType'], $reverseInvoiceBatchNumber);
        $isLiveApiCallStep2 = true;
        if (isset($sageLogArray[$step]) && $sageLogArray[$step]['status'] == SageEnum::STATUS_SUCCESS) {
            if ($sageLogArray[$step]['sage_end_point'] !== $payLoadOptions['endPoint']) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SAGE API: Reversal invoice number updated - Previous batch number: '.$sageLogArray[$step]['sage_end_point'].' - Current batch number: '.$payLoadOptions['endPoint'].' - Send Update Code: '.$sendUpdateLog->code);
                $getNewResponse = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'] ?? [], 'GET');
                $sageLogArray[$step]['response'] = $getNewResponse;
                $sageLogArray[$step]['sage_end_point'] = $payLoadOptions['endPoint'];
                SageApiLog::where('id', $sageLogArray[$step]['id'])->update([
                    'sage_end_point' => $payLoadOptions['endPoint'],
                    'response' => $getNewResponse,
                ]);
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SAGE API: Response against current batch number updated - Send Update Code: '.$sendUpdateLog->code);
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SAGE API: getInvoiceDetails for '.$reversalInvoiceDetails['invoiceType'].' already sent for '.$sendUpdateLog->code);
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[$step]['response'], true);
        } else {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SAGE API: Send getInvoiceDetails for '.$sendUpdateLog->code);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'] ?? [], 'GET');
            $sageResponse = json_decode($resp, true);
        }

        if (empty($sageResponse)) {
            $errorMessage = 'Error while getting invoice from Sage for Reversal';
            $message = 'getInvoiceDetails failed';

            return $this->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $payLoadOptions, $sageResponse, $step, 23, SageEnum::STATUS_FAIL, $userId]);
        } else {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SAGE API: '.$sendUpdateLog->code.' - getInvoiceDetails completed successfully - Reversal Invoice Batch number: '.($sageResponse['BatchNumber'] ?? ''));
            if ($isLiveApiCallStep2) {
                $payLoadOptions['entry_type'] = $sageEntryType;
                $payLoadOptions['sage_request_type'] = $sageRequestType;

                $this->logSageApiCall($payLoadOptions, $sageResponse, $sendUpdateLog, $sendUpdateLog, $step, 23, SageEnum::STATUS_SUCCESS, $userId);
            }
        }

        $returnMessage['response'] = $sageResponse;
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Invoice Details fetched successfully';

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Getting Invoice from Sage process end - SendUpdateCode: '.$sendUpdateLog?->code);

        return $returnMessage;
    }

    public function postBookPolicyToSage($request, $quote)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        // Condition moved to Up to so that system alert user right away instead checking status after date processing
        if ($quote->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy has been already booked!');

            return ['status' => true, 'message' => 'Policy has been already booked!'];
        }

        // check sage is enabled or not
        if (! $this->isSageEnabled()) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Sage is not enabled');
            $returnMessage['message'] = 'Sage is not enabled';

            return $returnMessage;
        }
        $quoteType = $request->model_type;

        $quoteTypeId = QuoteTypes::getIdFromValue($request->model_type) ?? $quote->quote_type_id;

        if (in_array($quoteTypeId, EmbeddedProductRepository::ALLOWED_LOBS)) {

            $captureableEmbeddedTransactions = EmbeddedProductRepository::authorisedTransactions($quoteTypeId, $quote->id);
            if ($captureableEmbeddedTransactions->isNotEmpty()) {
                try {
                    EmbeddedProductRepository::capturePayment($quote->id, strtolower($quoteType));
                    $hasMedexOrEcbProduct = EmbeddedProductRepository::hasMedexOrEcbProduct($captureableEmbeddedTransactions);

                    // Return response only if EP has any MEDEX / ECB Product, otherwise proceed to Sage booking
                    if ($hasMedexOrEcbProduct) {
                        LoggerService::info(
                            'Embedded Product payment is being captured, once done, booking process will begin',
                            extra: $captureableEmbeddedTransactions->toArray()
                        );

                        return ['status' => true, 'message' => 'The embedded product payment is being captured, once done, booking process will begin.'];
                    }

                } catch (Exception $e) {
                    LoggerService::error('Embedded Product payment capture failed', [
                        'error' => $e->getMessage(),
                        'uuid' => $quote->uuid,
                    ]);

                    return ['status' => false, 'message' => 'Embedded Product payment capture failed'];
                }
            }
        }

        /* Check EP Booking */
        $isEPTransStatusReadyForSage = $this->isEmbeddedTransactionStatusReadyForSage($quote, $quoteTypeId);
        if (! $isEPTransStatusReadyForSage) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Please check the embedded transaction status for quote code: '.$quote->code.' as its not ready for sage yet!');

            return ['status' => false, 'message' => 'Please check the embedded transaction status for quote code : '.$quote->code.' as its not ready for sage yet!'];
        }
        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();

        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = Payment::where([
                'paymentable_id' => $quote->id,
                'paymentable_type' => $quote->getMorphClass(),
            ])->mainLeadPayment()->with('paymentSplits')->first();
        }
        $paymentSplits = $payment->paymentSplits;

        $data = ['id' => $quote->id, 'quoteTypeId' => $quoteTypeId];

        // This block is for collection type INSURER and having any Credit Card Payment
        // This specific block is added to handle tap payments
        $unpaidPaymentCount = $paymentSplits->whereNotIn('payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PAID])
            ->where('payment_method', PaymentMethodsEnum::CreditCard)
            ->select('id')
            ->count();
        $isInsurerPayment = $payment->isInsurerPayment();
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Payment Code: '.$payment->code.' - Unpaid payment count: '.$unpaidPaymentCount.' - Collection type: '.$payment->collection_type);
        if ($isInsurerPayment && $unpaidPaymentCount > 0) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Skipping Policy Book & Authorizing payment for '.$payment->code);
            $successMessage = $this->handleSplitPaymentApproval($quoteTypeId, $quote, $payment, $paymentSplits);
            if (! $successMessage) {
                return ['status' => false, 'message' => 'Error in approving payment - Book Policy'];
            }
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Payment Code: '.$payment->code.' - handleSplitPaymentApproval result: '.json_encode($successMessage));

            QuoteTag::updateOrCreate(
                [
                    'quote_type_id' => $quoteTypeId,
                    'quote_uuid' => $quote->uuid,
                    'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START,
                ],
                [
                    'value' => 1,
                ]
            );

            return ['status' => true, 'message' => 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!'];
        } else {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Payment Code: '.$payment->code.' - Capture payment process skip & proceeding with Policy Book process - Unpaid payment count: '.$unpaidPaymentCount.' - Is Insurer Payment: '.$isInsurerPayment);
        }

        $isHealthAUHLead = $this->isHealthAUHLead($quoteType, $quote);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Quote code: '.$quote->code.' - Is Health AUH Lead Check ', extra : [
            'isHealthAUHLead' => $isHealthAUHLead,
        ]);
        if ($isHealthAUHLead) {

            if (! (app(QuoteStatusService::class)->isPolicySentLogExists($quote->id))) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Send Customer Documents to customer after booking of : '.$quote->code.' ##################################');
                // dispath job to send email
                SendBookPolicyDocumentsJob::dispatch($request, $quote->code);
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Policy Book : mark status as policy booked for : '.$quote->code.' ##################################');

            $this->updateAndLogQuoteStatus($quote, $quoteTypeId, QuoteStatusEnum::PolicyBooked, auth()->id());

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Policy Book : Status updated to: '.$quote->quote_status_id.' for '.$quote->code.' ##################################');

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Policy Book : straightforwardPayments for : '.$quote->code.' ##################################');
            (new CentralService)->straightforwardPayments($payment, $paymentSplits, $quote);
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ################################## Policy Book : straightforwardPayments for : '.$quote->code.' done ##################################');

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - ########## End of Policy Booked for : '.$quote->code.' ##########');

            return ['status' => true, 'message' => 'Policy is Booked'];

        }

        // Booking of Policies with zero price is only allowed for the policies having Credit Approval as Payment Method.
        $isPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
        $isPaymentMethodCreditApproved = $payment->payment_methods_code == PaymentMethodsEnum::CreditApproval;
        $isTotalPriceZero = $payment->total_price == 0;
        if (! $isPaymentMethodCreditApproved && $isTotalPriceZero && $isPaymentFrequencyUpfront) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Please check the payment as total price is set to zero while Payment Method is '.PaymentMethodsEnum::CreditApproval.' and Frequency is '.$payment->frequency.'. Please Select Credit Approval as your payment method and Upfront as Payment Frequency to Proceed!');

            return ['status' => false, 'message' => 'Please check the payment as total price is set to zero while Payment Method is '.PaymentMethodsEnum::CreditApproval.' and Frequency is '.$payment->frequency.'. Please Select Credit Approval as your payment method and Upfront as Payment Frequency to Proceed!'];
        }

        // payload
        $sageRequest = app(SagePayloadFactory::class)->sagePayLoad($request->model_type, $payment, $quote, $paymentSplits);
        $isPaymentPaidOrCreditApproved = $this->isPaymentPaidOrCreditApproved($payment, $paymentSplits, $sageRequest);
        $sageRequest->quoteCode = $quote?->code;
        $sageRequest->quoteTypeId = $quoteTypeId;
        $sageRequest->quoteType = $quoteType;
        $sageRequest->modelType = $quoteType;
        $sageRequest->sageProcessRequestType = SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST;

        // sage customer number generation
        $sageRequest->customerId = $this->verifySageCustomer($quote->customer_id, $data, $quote, 13);

        /* Check Sage Vendor ID, GL Account ID, Insurer Customer ID, and Sage Customer ID */
        $checkRequiredSageIds = $this->checkRequiredSageIds($sageRequest);
        if (! $checkRequiredSageIds['status']) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - '.$checkRequiredSageIds['message']);

            return $checkRequiredSageIds;
        }

        $commissionChargeIds = $paymentSplits->flatMap(function ($paymentSplit) {
            return $paymentSplit->paymentCharges()?->where('action_type', PaymentChargesEnum::ACTION_TYPE_CHARGE->value)
                ->pluck('transaction_id')
                ->toArray();
        })->toArray();
        $sageRequest->commissionChargeId = count($commissionChargeIds) > 0 ? implode(',', $commissionChargeIds) : '';
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Commission Charge IDs: '.$sageRequest->commissionChargeId);

        $this->createSageProcess($quote, $sageRequest, $request);

        if ($quote->quote_status_id != QuoteStatusEnum::POLICY_BOOKING_QUEUED) {
            $this->updateAndLogQuoteStatus($quote, $sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED, $sageRequest->userId);
        }

        $this->scheduleSageProcesses($sageRequest->insurerID);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - scheduleSageProcesses triggered for Insurer: '.$sageRequest->insurerID);

        return ['status' => true, 'message' => 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!'];
    }

    public function checkRequiredSageIds($sageRequest): array
    {
        $missingFields = [];
        if (empty($sageRequest->customerId)) {
            $missingFields[] = 'Customer Sage ID';
        }

        if (! $sageRequest->insurerGlLiaiblityAccount) {
            $missingFields[] = 'GL Account for Insurance Provider';
        }
        if (! $sageRequest->sageVenderId) {
            $missingFields[] = 'Sage Vendor ID for Insurance Provider';
        }
        if (! $sageRequest->sageInsurerCustomerId) {
            $missingFields[] = 'Sage Insurer Customer ID for Insurance Provider';
        }

        if (! empty($missingFields)) {
            $message = implode(', ', $missingFields).' not found.';
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - '.$message);

            return ['status' => false, 'message' => $message];
        }

        return ['status' => true, 'message' => ''];
    }

    public function bookPolicyOnSage($sageRequestDataArray)
    {
        [$sageRequest, $quote, $request] = $sageRequestDataArray;
        $quoteTypeId = $sageRequest->quoteTypeId;
        $userId = $sageRequest->userId;

        $sageLogArray = $quote->sageApiLogs->keyBy('step')->toArray();

        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();

        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = Payment::where([
                'paymentable_id' => $quote->id,
                'paymentable_type' => $quote->getMorphClass(),
            ])->mainLeadPayment()->with('paymentSplits')->first();
        }

        $paymentSplits = $payment->paymentSplits;

        $quote->userId = $sageRequest->userId;

        $isPolicyBookedOnSage = QuoteTag::where([
            'quote_type_id' => $quoteTypeId,
            'quote_uuid' => $quote->uuid,
            'name' => QuoteTagEnums::POLICY_BOOKED_ON_SAGE,
            'value' => 1,
        ])->first();

        LoggerService::info('--------------------------------Sage Policy Booking process started-------------------------------');

        if (! $isPolicyBookedOnSage) {

            LoggerService::info('Payment frequency: '.$payment->frequency);

            // create AR Prepayment Premium Receipt
            $createARPremiumPrepayment = $this->createARPrepaymentPremiumReceipts([$sageRequest, $quote, $payment, $paymentSplits]);
            if (! $createARPremiumPrepayment['status']) {
                return $createARPremiumPrepayment;
            }

            // create AP Prepayment Premium Receipt
            // TODO:: Need to update the logs and post in progress issue for AP Prepayment Premium Receipt
            /*$createAPPremiumPrepayment = $this->createAPPrepaymentPremiumReceipts([$sageRequest, $quote, $payment, $paymentSplits]);
            if (! $createAPPremiumPrepayment['status']) {
                return $createAPPremiumPrepayment;
            }*/

            $payment = $payment->refresh();
            $paymentSplits = $payment->paymentSplits;

            // Create AR Commission and Premium Invoice
            $extraDetails = ['sage_request_type' => ($payment->frequency == PaymentFrequency::UPFRONT) ? SageEnum::SRT_CREATE_AR_PREM_COMM_INV : SageEnum::SRT_CREATE_AR_SPPAY_INV];
            $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
            if (! $createARInvoicePremAndComm['status']) {
                return $createARInvoicePremAndComm;
            }

            // Create AP Premium Invoice
            $extraDetails['sage_request_type'] = ($payment->frequency == PaymentFrequency::UPFRONT) ? SageEnum::SRT_CREATE_AP_PREM_INV : SageEnum::SRT_CREATE_AP_SPPAY_INV;
            $createAPInvoicePrem = $this->createAPInvoicePrem([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
            if (! $createAPInvoicePrem['status']) {
                return $createAPInvoicePrem;
            }

            // Create AR Discount Invoice
            $extraDetails['sage_request_type'] = SageEnum::SRT_CREATE_AR_DISC_INV;
            $createARInvoiceDis = $this->createARInvoiceDis([$sageRequest, $quote, $sageLogArray, $extraDetails]);
            if (! $createARInvoiceDis['status']) {
                return $createARInvoiceDis;
            }

            // Apply Prepayments for AR Invoice
            $extraDetails['sage_request_type'] = SageEnum::SRT_CREATE_PAY_REC_ONE_INV;
            $applyPaymentARInvoices = $this->applyPaymentARInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
            if (! $applyPaymentARInvoices['status']) {
                return $applyPaymentARInvoices;
            }

            // Apply Prepayments for AP Invoice
            /*$applyPaymentAPInvoices = $this->applyPaymentAPInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
            if (! $applyPaymentAPInvoices['status']) {
                return $applyPaymentAPInvoices;
            }*/

            QuoteTag::create([
                'quote_type_id' => $quoteTypeId,
                'quote_uuid' => $quote->uuid,
                'name' => QuoteTagEnums::POLICY_BOOKED_ON_SAGE,
                'value' => 1,
            ]);

            LoggerService::info('--------------------------------Sage Policy Booking process completed-------------------------------');
        } else {
            LoggerService::info('--------------------------------Sage Policy Already Booked-------------------------------');
        }

        $isTapPaymentGateway = $payment->payment_gateway_id == PaymentGatewayEnum::PAYMENT_GATEWAY_TAP;
        $isLobAllowedForEmbeddedProductBooking = $this->isLobAllowedForEmbeddedProductBooking($quoteTypeId);
        $ePTransactions = $this->getEPTransactions($quote, $quoteTypeId) ?? [];
        foreach ($ePTransactions as $ePTransaction) {
            LoggerService::info('Embedded Product booking checks', extra : [
                'isLobAllowedForEmbeddedProductBooking' => $isLobAllowedForEmbeddedProductBooking,
                'epPTransaction' => $ePTransaction?->code,
                'isTapPaymentGateway' => $isTapPaymentGateway,
            ]);
            if ($isLobAllowedForEmbeddedProductBooking && $ePTransaction && $isTapPaymentGateway) {
                LoggerService::info('--------------------------------Embedded Product Sage booking process started-------------------------------');
                $embeddedProductSageBookingResponse = (new SageApiEmbeddedProductService)->bookEmbeddedProductOnSage([$quote, $sageRequest, $ePTransaction], $ePTransaction?->product?->embeddedProduct?->short_code);
                LoggerService::info('--------------------------------Embedded Product Sage booking process completed-------------------------------', extra : $embeddedProductSageBookingResponse);
                if (! $embeddedProductSageBookingResponse['status']) {
                    return $embeddedProductSageBookingResponse;
                }
            }
        }

        $skipBookPolicyDocumentJob = false;
        if ($quoteTypeId === QuoteTypeId::Travel) {
            $quote->load('policyIssuance');
            if ($quote->policyIssuance?->status == PolicyIssuanceEnum::COMPLETED_STATUS && ! $quote->advisor_id) {
                $skipBookPolicyDocumentJob = true;
            }
        }

        LoggerService::info('Skipping book policy document job', extra: [
            'skipBookPolicyDocumentJob' => $skipBookPolicyDocumentJob ? 'Yes' : 'No',
        ]);
        if (! $skipBookPolicyDocumentJob && ! (app(QuoteStatusService::class)->isPolicySentLogExists($quote->id))) {
            LoggerService::info('Dispatching job to send customer documents after policy booking');
            // dispath job to send email
            SendBookPolicyDocumentsJob::dispatch($request, $quote->code);
        }

        LoggerService::info('Marking quote status as Policy Booked');
        $this->updateAndLogQuoteStatus($quote, $quoteTypeId, QuoteStatusEnum::PolicyBooked, $userId);

        LoggerService::info('Quote status updated successfully', extra: [
            'QuoteStatusId' => $quote->quote_status_id,
        ]);

        LoggerService::info('--------------------------------Straightforward payments process started-------------------------------');
        (new CentralService)->straightforwardPayments($payment, $paymentSplits, $quote);
        LoggerService::info('--------------------------------Straightforward payments process completed-------------------------------');

        LoggerService::info('--------------------------------Payment allocation status update started-------------------------------');
        $this->updatePaymentAllocationStatus($quote);
        LoggerService::info('--------------------------------Payment allocation status update completed-------------------------------');

        LoggerService::info('--------------------------------Sage Policy Booking process completed-------------------------------');

        return ['status' => true, 'message' => 'Policy is Booked'];
    }

    private function createARPrepaymentPremiumReceipts($sageRequestDataArray, $splitAmount = null)
    {
        [$sageRequest, $quote, $payment, $paymentSplits] = $sageRequestDataArray;
        $prepaymentResponses = [];
        $paidPaymentSplits = $payment->paymentSplits()->whereIn('payment_status_id', [PaymentStatusEnum::PAID, PaymentStatusEnum::PARTIALLY_PAID, PaymentStatusEnum::CAPTURED])->get();

        foreach ($paidPaymentSplits as $paymentSplit) {
            $isPaymentMethodCA = in_array($paymentSplit->payment_method, [PaymentMethodsEnum::CreditApproval]);
            if ($isPaymentMethodCA) {
                LoggerService::info('Posting of prepayment skipped due to credit approval', extra : ['PaymentSplitCode' => $paymentSplit->code, 'SerialNumber' => $paymentSplit->sr_no]);
                $prepaymentResponses[] = ['status' => true, 'message' => 'Posting of prepayment skipped due to credit approval : '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no];

                continue;
            }
            $createPrepaymentReceiptResponse = $this->createARPrepaymentPremiumReceipt($sageRequest, $quote, $payment, $paymentSplit);
            $prepaymentResponses[] = $createPrepaymentReceiptResponse;
        }

        // Check if there is any failed AR Prepayment Receipt
        foreach ($prepaymentResponses as $item) {
            if ($item['status'] === false) {
                return ['status' => false, 'message' => $item['message']];
            }
        }

        return ['status' => true, 'message' => 'AR Prepayment Receipts posted on sage.'];

    }

    private function createAPPrepaymentPremiumReceipts($sageRequestDataArray, $splitAmount = null)
    {
        [$sageRequest, $quote, $payment, $paymentSplits] = $sageRequestDataArray;
        $prepaymentResponses = [];
        $paidPaymentSplits = $payment->paymentSplits()->whereIn('payment_status_id', [PaymentStatusEnum::PAID, PaymentStatusEnum::PARTIALLY_PAID, PaymentStatusEnum::CAPTURED])->get();

        foreach ($paidPaymentSplits as $paymentSplit) {

            if ($paymentSplit->payment_method == PaymentMethodsEnum::CreditApproval) {
                LoggerService::info('Posting of AP prepayment skipped due to credit approval', extra : ['PaymentSplitCode' => $paymentSplit->code, 'SerialNumber' => $paymentSplit->sr_no]);
                $prepaymentResponses[] = ['status' => true, 'message' => 'Posting of AP prepayment skipped due to credit approval : '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no];

                continue;
            }
            $createPrepaymentReceiptResponse = $this->createAPPrepaymentPremiumReceipt($sageRequest, $quote, $payment, $paymentSplit);
            $prepaymentResponses[] = $createPrepaymentReceiptResponse;

        }

        // Check if there is any failed AP Prepayment Receipt
        foreach ($prepaymentResponses as $item) {
            if ($item['status'] === false) {
                return ['status' => false, 'message' => $item['message']];
            }
        }

        return ['status' => true, 'message' => 'AP Prepayment Receipts posted on sage.'];
    }

    public function createARPrepaymentPremiumReceipt($sageRequest, $quote, $payment, $paymentSplit, $splitAmount = null)
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'documentNumber' => null, 'sageCustomerNumber' => null];
        $sageApiService = new SageApiService;

        $isAlreadyPosted = false;
        $sageLogArray = $paymentSplit->sageApiLogs->keyBy('step')->toArray();
        $sendUpdateLog = $paymentSplit->payment?->sendUpdateLog;
        $quoteTypeId = $sageRequest->quoteTypeId ?? QuoteTypes::getIdFromValue($sageRequest->quoteType);
        $customerData = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];
        $quoteDetails = $sendUpdateLog ?? $quote;

        if ($sendUpdateLog) {
            $quoteDetails->fill([
                'advisor_id' => $quote?->advisor_id ?? null,
                'customer_id' => $quote?->customer_id,
                'policy_booking_date' => $sendUpdateLog->booking_date,
            ]);
        }

        $sageRequest = SagePayloadFactory::globalSagePrepaymentReceiptPayloadData([$quoteDetails, $payment, $paymentSplit, $sageRequest, $splitAmount]);
        $sageCustomerNumberResponse = $sageApiService->getSageCustomerNumber($quoteDetails, $sageRequest->customer_id, $customerData, $paymentSplit, $sageRequest->advisor_id);
        if ($sageCustomerNumberResponse['status'] === false) {
            $response['message'] = $sageCustomerNumberResponse['message'];

            return $response;
        }

        $response['sageCustomerNumber'] = $sageCustomerNumberResponse['sageCustomerNumber'];
        $sageRequest->sage_customer_number = $sageCustomerNumberResponse['sageCustomerNumber'];

        $payLoadOptions = SagePayloadFactory::createPrepaymentReceiptPayload($sageRequest);

        $isLiveApiCallStep2 = true;
        if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[2]['response'], true);
            LoggerService::info('AR Prepayment Receipts batch already sent', extra : [
                'BatchNumber' => $sageResponse['BatchNumber'],
                'PaymentCode' => $paymentSplit->code,
                'SerialNumber' => $paymentSplit->sr_no,
            ]);
        } else {
            $createPremiumPrepaymentResponse = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($createPremiumPrepaymentResponse, true);
            LoggerService::info('AR Prepayment Receipts batch created', extra : [
                'BatchNumber' => $sageResponse['BatchNumber'],
                'PaymentCode' => $paymentSplit->code,
                'SerialNumber' => $paymentSplit->sr_no,
            ]);
        }

        if (isset($sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'])) {
            if (! $paymentSplit->sage_reciept_id) {
                $documentNumberForReceipt = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
                $this->handleWithDeadlockRetries(function () use ($paymentSplit, $documentNumberForReceipt) {
                    $paymentSplit->update(['sage_reciept_id' => $documentNumberForReceipt]);
                }, 5);
            }
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $paymentSplit, $quoteDetails, 2, 4, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
            }

            $isLiveApiCallStep3 = true;
            $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptArPayment($sageResponse['BatchNumber']);

            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep3 = false;
                $readyToPostResponse = $sageLogArray[3]['response'];
                LoggerService::info('AR Prepayment Ready To Post batch already sent', extra : [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                    'PaymentCode' => $paymentSplit->code,
                    'SerialNumber' => $paymentSplit->sr_no,
                ]);
            } else {
                $readyToPostResponse = $sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
                LoggerService::info('AR Prepayment Ready To Post batch sent', extra : [
                    'BatchNumber' => $sageResponse['BatchNumber'],
                    'PaymentCode' => $paymentSplit->code,
                    'SerialNumber' => $paymentSplit->sr_no,
                    'ReadyToPostResponse' => $readyToPostResponse,
                ]);
            }

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post AR Prepayment Ready To Post batch', extra : [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'PaymentCode' => $paymentSplit->code,
                        'SerialNumber' => $paymentSplit->sr_no,
                        'Error' => $readyToPostArray['error']['message']['value'],
                    ]);

                    $aRReceiptBatch = $sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    LoggerService::info('Checking status of AR Prepayment Receipts batch', extra : [
                        'PaymentCode' => $paymentSplit->code,
                        'SerialNumber' => $paymentSplit->sr_no,
                        'aRReceiptBatch' => $aRReceiptBatch,
                    ]);
                    $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                    if (isset($aRReceiptBatch['BatchStatus']) && $aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAr, "", $paymentSplit, $quoteDetails, 3, 4, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aRReceiptBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get AR Prepayment Receipts batch status', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'PaymentCode' => $paymentSplit->code,
                            'SerialNumber' => $paymentSplit->sr_no,
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $paymentSplit, $quoteDetails, 3, 4, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);
                        $response['message'] = 'Failed to get Prepayment Batch Status - Ref:'.$quoteDetails->code;

                        return $response;
                    } else {
                        LoggerService::info('Error while making ready to post to sage', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'PaymentCode' => $paymentSplit->code,
                            'SerialNumber' => $paymentSplit->sr_no,
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $paymentSplit, $quoteDetails, 3, 4, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);
                        $response['message'] = 'Error while making ready to post to sage - Ref:'.$quoteDetails->code;

                        return $response;
                    }
                } else {
                    if ($isLiveApiCallStep3) {
                        LoggerService::info('Logging AR Prepayment Ready To Post batch successfully', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'PaymentCode' => $paymentSplit->code,
                            'SerialNumber' => $paymentSplit->sr_no,
                        ]);
                        $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $paymentSplit, $quoteDetails, 3, 4, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                    }
                }
            } else {
                if ($isLiveApiCallStep3) {
                    LoggerService::info('Logging AR Prepayment Ready To Post batch successfully with empty response', extra : [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'PaymentCode' => $paymentSplit->code,
                        'SerialNumber' => $paymentSplit->sr_no,
                    ]);
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $paymentSplit, $quoteDetails, 3, 4, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                }
            }

            if ($this->shouldCreateAndSchedulePostPrepayment($quote, $paymentSplit, $sendUpdateLog)) { /* Handle NRA case where payment is approved after policy/send update is booked */
                LoggerService::info('Scheduling post prepayment process for Payment Split', extra : [
                    'PaymentSplitID' => $paymentSplit?->id,
                    'PaymentSplitCode' => $paymentSplit?->code,
                ]);
                $postPrepayment = $sageApiService->schedulePostPrepaymentToSageProcess([$quote, $sageRequest->quoteType, $paymentSplit, $sendUpdateLog]);
                if (! $postPrepayment['status']) {
                    LoggerService::info('Failed to schedule post prepayment for Payment Split', extra : [
                        'PaymentSplitID' => $paymentSplit?->id,
                        'PaymentSplitCode' => $paymentSplit?->code,
                        'Data' => json_encode($postPrepayment),
                    ]);
                } else {
                    LoggerService::info('Post prepayment scheduled for Payment Split', extra : [
                        'PaymentSplitID' => $paymentSplit?->id,
                        'PaymentSplitCode' => $paymentSplit?->code,
                        'Data' => json_encode($postPrepayment),
                    ]);
                }
            } else {
                LoggerService::info('Post prepayment for Payment Split', extra : [
                    'PaymentSplitID' => $paymentSplit?->id,
                    'PaymentSplitCode' => $paymentSplit?->code,
                    'BatchNumber' => $sageResponse['BatchNumber'],
                ]);
                $isLiveApiCallStep4 = true;
                $aRPostReceipts = SagePayloadFactory::aRPostReceiptsPayment($sageResponse['BatchNumber']);
                if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == SageEnum::STATUS_SUCCESS) {
                    $isLiveApiCallStep4 = false;
                    $postedResponse = json_decode($sageLogArray[4]['response'], true);
                    LoggerService::info('AR Prepayment Receipts batch already posted', extra : [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'PaymentCode' => $paymentSplit->code,
                        'SerialNumber' => $paymentSplit->sr_no,
                    ]);
                } else {
                    if ($isAlreadyPosted && isset($aRPostReceipts) || isset($sageLogArray[4]) && $sageLogArray[4]['status'] == SageEnum::STATUS_FAIL) {
                        if ($isAlreadyPosted) {
                            LoggerService::info('AR Prepayment Receipts batch already posted', extra : [
                                'BatchNumber' => $sageResponse['BatchNumber'],
                                'PaymentCode' => $paymentSplit->code,
                                'SerialNumber' => $paymentSplit->sr_no,
                            ]);
                            $postedResponse = $aRPostReceipts['payload'];
                        } else {
                            LoggerService::info('Checking status of AR Prepayment Receipts batch', extra : [
                                'BatchNumber' => $sageResponse['BatchNumber'],
                                'PaymentCode' => $paymentSplit->code,
                                'SerialNumber' => $paymentSplit->sr_no,
                            ]);

                            $aRReceiptBatch = $sageApiService->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                            $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                            if (! isset($aRReceiptBatch['BatchStatus'])) {
                                LoggerService::info('Failed to get status of AR Prepayment Receipts batch', extra : [
                                    'BatchNumber' => $sageResponse['BatchNumber'],
                                    'PaymentCode' => $paymentSplit->code,
                                    'SerialNumber' => $paymentSplit->sr_no,
                                ]);
                                $response['message'] = 'Failed to get status of AR Prepayment Receipts batch - Ref:'.$quote->code;

                                return $response;
                            }

                            if (isset($aRReceiptBatch['BatchStatus']) && $aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                                $postedResponse = $aRPostReceipts['payload'];
                                $isAlreadyPosted = true;
                            }
                        }
                    }

                    if (! $isAlreadyPosted) {
                        $postedResponse = $sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                        $postedResponse = json_decode($postedResponse, true);
                        LoggerService::info('AR Prepayment Receipts batch posted', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'PaymentCode' => $paymentSplit->code,
                            'SerialNumber' => $paymentSplit->sr_no,
                        ]);

                    }
                }

                if (isset($postedResponse['error'])) {
                    LoggerService::info('Error while posting to sage', extra : [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'PaymentCode' => $paymentSplit->code,
                        'SerialNumber' => $paymentSplit->sr_no,
                        'Error' => $postedResponse['error'],

                    ]);
                    $response['message'] = 'Error while posting to sage - Ref:'.$quote->code;
                    $sageErrorMessage = $postedResponse['error']['message']['value'] ?? $postedResponse['error'] ?? null;
                    if ($this->sageHasProcessingConflict($sageErrorMessage)) {
                        $response['message'] = SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE;
                    }
                    LoggerService::info('Logging error while posting to sage', extra : [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'PaymentCode' => $paymentSplit->code,
                        'SerialNumber' => $paymentSplit->sr_no,
                    ]);

                    $this->logSageApiCall($aRPostReceipts, $postedResponse, $paymentSplit, $quoteDetails, 4, 4, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);

                    return $response;
                } else {
                    if ($isLiveApiCallStep4) {
                        LoggerService::info('Logging AR Prepayment Receipts batch successfully', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'PaymentCode' => $paymentSplit->code,
                            'SerialNumber' => $paymentSplit->sr_no,
                        ]);

                        $this->logSageApiCall($aRPostReceipts, $postedResponse, $paymentSplit, $quoteDetails, 4, 4, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                    }
                }
            }

            LoggerService::info('Successfully created AR Prepayment Receipt', extra : [
                'PaymentCode' => $paymentSplit->code,
                'SerialNumber' => $paymentSplit->sr_no,
            ]);
            $response['status'] = true;
            $response['message'] = 'Prepayment created';
        } else {
            LoggerService::info('Error: Document number not generated from Sage', extra : [
                'PaymentCode' => $paymentSplit->code,
                'SerialNumber' => $paymentSplit->sr_no,
                'CreateReceiptResponse' => $sageResponse,
            ]);
            $this->logSageApiCall($payLoadOptions, $sageResponse, $paymentSplit, $quoteDetails, 2, 4, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);
            $response['message'] = 'Document number not generated from sage - Ref:'.$quoteDetails->code;
        }

        return $response;
    }

    public function createAPPrepaymentPremiumReceipt($sageRequest, $quote, $payment, $paymentSplit, $splitAmount = null)
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'documentNumber' => null];
        $sageRequest = SagePayloadFactory::globalSagePrepaymentReceiptPayloadData([$quote, $payment, $paymentSplit, $sageRequest, $splitAmount]);

        $isAlreadyPosted = false;
        $sageLogArray = $paymentSplit->sageApiLogs->keyBy('step')->toArray();
        $sendUpdateLog = $paymentSplit->payment?->sendUpdateLog;
        $quoteDetails = $sendUpdateLog ?? $quote;

        $payLoadOptions = SagePayloadFactory::createAPPrepaymentReceiptPayload($sageRequest);

        $isLiveApiCallStep5 = true;
        if (isset($sageLogArray[5]) && $sageLogArray[5]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep5 = false;
            $sageResponse = json_decode($sageLogArray[5]['response'], true);
        } else {

            $createAPPremiumPaymentReceipt = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($createAPPremiumPaymentReceipt, true);
        }

        if (isset($sageResponse['PaymentsAdjustments'][0]['DocumentNumber'])) {
            if (! $paymentSplit->sage_ap_payment_receipt_id) {
                $documentNumberForReceipt = $sageResponse['PaymentsAdjustments'][0]['DocumentNumber'];
                $this->handleWithDeadlockRetries(function () use ($paymentSplit, $documentNumberForReceipt) {
                    $paymentSplit->update(['sage_ap_payment_receipt_id' => $documentNumberForReceipt]);
                }, 5);
            }
            LoggerService::info('Created AP Prepayment Receipts batch', extra : [
                'BatchNumber' => $sageResponse['BatchNumber'],
                'PaymentCode' => $paymentSplit->code,
                'SerialNumber' => $paymentSplit->sr_no,
            ]);
            if ($isLiveApiCallStep5) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $paymentSplit, $quoteDetails, 5, 7, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
            }

            $isLiveApiCallStep6 = true;
            $readyToPostReceiptAp = SagePayloadFactory::readyToPostAPPaymentReceiptPayload($sageResponse['BatchNumber']);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAp['endPoint'], $readyToPostReceiptAp['payload'], 'PATCH');

            if ($readyToPostResponse !== '') {
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info(self::class.' fn:'.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' SAGE API Payments Error: Failed to post AP Prepayment Receipts batch '.$sageResponse['BatchNumber'].' Error: '.$readyToPostArray['error']['message']['value']);

                    $aPReceiptBatch = $this->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                    LoggerService::info(self::class.' fn:'.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' SAGE API Payments: Status of AP Prepayment Receipts batch: '.$aPReceiptBatch);
                    $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                    if (isset($aPReceiptBatch['BatchStatus']) && $aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $paymentSplit, $quote, 6, 7, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                        $isAlreadyPosted = true;
                    } elseif (! isset($aPReceiptBatch['BatchStatus'])) {
                        LoggerService::info(self::class.' fn:'.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' SAGE API Payments Error: Failed to get Prepayment Batch Status for AP Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $paymentSplit, $quoteDetails, 6, 7, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);
                        $response['message'] = 'Failed to get Prepayment Batch Status - Ref:'.$quoteDetails->code;

                        return $response;
                    } else {
                        LoggerService::info(self::class.' fn:'.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' SAGE API Payments Error: Failed to post AP Prepayment Receipts batch '.$sageResponse['BatchNumber']);
                        $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $paymentSplit, $quoteDetails, 6, 7, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);
                        $response['message'] = 'Error while making ready to post to sage - Ref:'.$quoteDetails->code;

                        return $response;
                    }
                } else {
                    $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $paymentSplit, $quoteDetails, 6, 7, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                }
            } else {
                if ($isLiveApiCallStep6) {
                    $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $paymentSplit, $quoteDetails, 6, 7, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                }
            }

            if ($this->shouldCreateAndSchedulePostPrepayment($quote, $paymentSplit, $sendUpdateLog)) { /* Handle NRA case where payment is approved after policy/send update is booked */
                LoggerService::info(self::class.' fn:'.__FUNCTION__.' trigger post prepayment schedule for PaymentSplitID : '.$paymentSplit->id);
                $postPrepayment = (new SageApiService)->schedulePostPrepaymentToSageProcess([$quote, $sageRequest->quoteType, $paymentSplit, $sendUpdateLog]);
                if (! $postPrepayment['status']) {
                    LoggerService::info(self::class.' fn:'.__FUNCTION__.' failed to scheduled post prepayment for PaymentSplitID : '.$paymentSplit->id, ['data' => json_encode($postPrepayment)]);
                } else {
                    LoggerService::info(self::class.' fn:'.__FUNCTION__.' post prepayment scheduled for PaymentSplitID : '.$paymentSplit->id, ['data' => json_encode($postPrepayment)]);
                }
            } else {
                LoggerService::info(self::class.' fn:'.__FUNCTION__.' post prepayment for PaymentSplitID : '.$paymentSplit->id, ['BatchNumber' => $sageResponse['BatchNumber']]);
                $isLiveApiCallStep7 = true;
                $aPPostReceipts = SagePayloadFactory::postAPPaymentReceiptPayload($sageResponse['BatchNumber']);
                if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == SageEnum::STATUS_SUCCESS) {
                    $isLiveApiCallStep7 = false;
                    $postedResponse = json_decode($sageLogArray[7]['response'], true);
                } else {
                    $postedResponse = $this->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                    $postedResponse = json_decode($postedResponse, true);
                }

                if ($isAlreadyPosted && isset($aPPostReceipts)) {
                    $this->logSageApiCall($aPPostReceipts, $postedResponse, $paymentSplit, $quote, 7, 7, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                } else {
                    if (isset($postedResponse['error'])) {
                        LoggerService::info(self::class.' fn:'.__FUNCTION__.'Child payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' SAGE API Payments Error: Failed to post AP Receipts for batch '.$sageResponse['BatchNumber']);
                        $response['message'] = 'Error while posting to sage - Ref:'.$quote->code;
                        $sageErrorMessage = $postedResponse['error']['message']['value'] ?? $postedResponse['error'] ?? null;
                        if ($this->sageHasProcessingConflict($sageErrorMessage)) {
                            $response['message'] = SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE;
                        }
                        $this->logSageApiCall($aPPostReceipts, $postedResponse, $paymentSplit, $quoteDetails, 7, 7, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);

                        return $response;
                    } else {
                        if ($isLiveApiCallStep7) {
                            $this->logSageApiCall($aPPostReceipts, $postedResponse, $paymentSplit, $quoteDetails, 7, 7, SageEnum::STATUS_SUCCESS, $sageRequest->advisor_id);
                        }
                    }
                }
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' - SAGE API Payments: Successfully created AP receipt');
            $response['status'] = true;
            $response['message'] = 'AP Prepayment created';
        } else {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - SAGE API: Quote Code: '.$quote->code.' - Payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' - Error: Document number not generated from Sage');
            $this->logSageApiCall($payLoadOptions, $sageResponse, $paymentSplit, $quoteDetails, 5, 7, SageEnum::STATUS_FAIL, $sageRequest->advisor_id);
            $response['message'] = 'Document number not generated from sage - Ref:'.$quoteDetails->code;
        }

        return $response;
    }

    public function shouldCreateAndSchedulePostPrepayment($quote, $paymentSplit, $sendUpdateLog = null)
    {
        if (! $sendUpdateLog) {
            $sendUpdateLog = $paymentSplit?->payment?->sendUpdateLog;
        }

        $isSendUpdateBooked = $sendUpdateLog?->status == SendUpdateLogStatusEnum::UPDATE_BOOKED;

        $isPolicyBooked = $quote?->quote_status_id == QuoteStatusEnum::PolicyBooked;

        return ($isPolicyBooked && ! $sendUpdateLog) || ($sendUpdateLog && $isSendUpdateBooked);

    }

    private function createARInvoicePremAndComm($sageRequestDataArray)
    {
        $sageRequestDataArray = array_pad($sageRequestDataArray, 6, []);
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;

        $isPaymentFrequencyUpfront = $extraDetails['reversalFrequency'] ?? ($payment->frequency == PaymentFrequency::UPFRONT);
        if ($isPaymentFrequencyUpfront) {
            return $this->createUpfrontARInvoicePremAndComm([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        } else {
            return $this->createNonUpfrontARInvoicePremAndComm([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        }

    }

    private function createUpfrontARInvoicePremAndComm($sageRequestDataArray): array
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $sageEntryType = $extraDetails['sage_entry_type'] ?? SageEnum::SCT_STRAIGHT;
        $reverseInvoiceDetails = $extraDetails['sageReversalInvoice'] ?? '';
        $userId = $extraDetails['userId'] ?? null;
        $totalSteps = 13;
        $stepsMapping = ['step_1' => 2, 'step_2' => 3, 'step_3' => 4];
        $isAlreadyPosted = false;

        LoggerService::info('Starting Upfront AR Invoice Premium and Commission creation', extra: [
            'entry_type' => $sageEntryType,
        ]);

        switch ($sageEntryType) {
            case SageEnum::SCT_REVERSAL:
                $totalSteps = 22;
                $stepsMapping = ['step_1' => 3, 'step_2' => 4, 'step_3' => 5];
                break;
            case SageEnum::SCT_CORRECTION:
                $totalSteps = 22;
                $stepsMapping = ['step_1' => 6, 'step_2' => 7, 'step_3' => 8];
                break;
        }

        $isLiveApiCallStep2 = true;
        $payLoadOptions = SagePayloadFactory::createARInvoicePremAndComm(request: $sageRequest, type: $sageEntryType, reversalDetails: $reverseInvoiceDetails, extras: $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('AR Invoice Premium and Commission already sent');
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending AR Invoice Premium and Commission to Sage');
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            LoggerService::info('AR Invoice Premium and Commission batch number - '.$sageResponse['BatchNumber']);
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }

            $isLiveApiCallStep3 = true;
            $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr(batchNumber: $sageResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
            
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                $isLiveApiCallStep3 = false;
                $readyToPostResponse = $sageLogArray[$stepsMapping['step_2']]['response'];
                LoggerService::info('AR Invoice Premium and Commission ready to post already sent', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber']
                ]);
            } else {
                LoggerService::info('Sending AR Invoice Premium and Commission ready to post to Sage', extra: [
                    'BatchNumber' => $sageResponse['BatchNumber']
                ]);
                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making Ar invoice & prem ready to post to sage';
                $readyToPostArray = json_decode($readyToPostResponse, true);

                if (isset($readyToPostArray['error']['message']['value'])) {
                    LoggerService::info('Failed to post AR Invoice Premium and Commission Ready To Post batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'Error' => $readyToPostArray['error']['message']['value']
                    ]);

                    $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    LoggerService::info('Checking status of AR Invoice Premium and Commission batch', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber'],
                        'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found'
                    ]);

                    if (isset($arInvoiceBatch['BatchStatus']) && $arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        $this->logSageApiCall($readyToPostInvoiceAr, "", $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                        $isAlreadyPosted = true;
                    } elseif (! isset($arInvoiceBatch['BatchStatus'])) {
                        LoggerService::info('Failed to get AR Invoice Premium and Commission batch status', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber']
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId);
                        $message = 'Failed to get status of AR Invoice Premium and Commission batch - '.$sageResponse['BatchNumber'].' failed';

                        return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                    } else {
                        LoggerService::info('Error while making AR Invoice Premium and Commission ready to post to sage', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber']
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId);
                        $message = 'Failed to post AR Invoice Premium and Commission Ready To Post batch - '.$sageResponse['BatchNumber'].' failed';

                        return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                    }
                } else {
                    if ($isLiveApiCallStep3) {
                        LoggerService::info('Logging AR Invoice Premium and Commission Ready To Post batch successfully', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber']
                        ]);
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }
            } else {
                if ($isLiveApiCallStep3) {
                    LoggerService::info('Logging AR Invoice Premium and Commission Ready To Post batch successfully with empty response', extra: [
                        'BatchNumber' => $sageResponse['BatchNumber']
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

            $isLiveApiCallStep4 = true;
            $aRPostInvoices = SagePayloadFactory::aRPostInvoices(batchNumber: $sageResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('AR Invoice Premium and Commission already posted');
                $isLiveApiCallStep4 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                if (($isAlreadyPosted && isset($aRPostInvoices)) || (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL)) {
                    if ($isAlreadyPosted) {
                        LoggerService::info('AR Invoice Premium and Commission batch already posted', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                        ]);
                        $postedResponse = $aRPostInvoices['payload'];
                    } else {
                        LoggerService::info('Checking status of AR Invoice Premium and Commission batch', extra : [
                            'BatchNumber' => $sageResponse['BatchNumber']
                        ]);
                        $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                        $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                        LoggerService::info('Status of AR Invoice Premium and Commission batch', extra: [
                            'BatchNumber' => $sageResponse['BatchNumber'],
                            'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found'
                        ]);

                        if (! isset($arInvoiceBatch['BatchStatus'])) {
                            $message = 'Upfront - AR Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;
    
                            return $returnMessage;
                        }

                        if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('AR Invoice Premium and Commission batch already posted', extra : [
                                'BatchNumber' => $sageResponse['BatchNumber']
                            ]);
                            $postedResponse = $aRPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info('Sending AR Invoice Premium and Commission AR Post to Sage');
                    $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making Ar invoice & prem Posted to sage';
                $message = 'aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
            LoggerService::info('AR Invoice Premium and Commission AR Post completed successfully', extra : [
                'BatchNumber' => $sageResponse['BatchNumber'],
            ]);
            if ($isLiveApiCallStep4) {
                // TODO:: This need to be improve, posted response undefined if status not posted
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        } else {
            $errorMessage = 'Ar invoice & prem failed from sage';
            $message = 'createARInvoicePremAndComm  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }

        LoggerService::info('Completed Upfront AR Invoice Premium and Commission creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Premium and Commission invoice created on sage';

        return $returnMessage;
    }

    private function createNonUpfrontARInvoicePremAndComm($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;

        $sageEntryType = $extraDetails['sage_entry_type'] ?? SageEnum::SCT_STRAIGHT;
        $reverseInvoiceDetails = $extraDetails['sageReversalInvoice'] ?? '';
        $userId = $extraDetails['userId'] ?? null;

        $totalSteps = 13;
        $stepsMapping = ['step_1' => 2, 'step_2' => 3, 'step_3' => 4, 'step_4' => 5];
        $isAlreadyPosted = false;

        LoggerService::info('Starting Non-Upfront AR Invoice Premium and Commission creation', extra: [
            'entry_type' => $sageEntryType,
        ]);

        if ($sageEntryType == SageEnum::SCT_CORRECTION) {
            $totalSteps = 24;
            $stepsMapping = ['step_1' => 6, 'step_2' => 7, 'step_3' => 8, 'step_4' => 9];
        }

        $isLiveApiCallStep2 = true;
        $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($sageRequest, $paymentSplits, $sageEntryType, $reverseInvoiceDetails, $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('AR Invoice Premium and Commission non upfront already sent');
            $isLiveApiCallStep2 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending AR Invoice Premium and Commission non upfront to Sage');
            $resp = $this->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (empty($postedResponse['BatchNumber'])) {
            $errorMessage = 'ar split payment failed from sage';
            $message = 'createARInvoiceSplitPayments  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createARInvoiceSplitPayments, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }
        LoggerService::info('AR Invoice Premium and Commission non upfront batch number - '.$postedResponse['BatchNumber']);
        $batchNumber = $postedResponse['BatchNumber'];
        if ($isLiveApiCallStep2) {
            $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
        }

        if ($sageEntryType !== SageEnum::SCT_REVERSAL) {
            $url = 'AR/ARInvoiceBatches('.$batchNumber.')';
            LoggerService::info('Fetching AR Invoice Premium and Commission non upfront batch details from Sage');
            $resp = $this->postToSage300($url, [], 'GET');
            $postedResponse = json_decode($resp, true);

            if (empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                $errorMessage = 'Error while getting ar2 split payments from sage';
                $message = 'get AR/ARInvoiceBatches for batchNumber : '.$batchNumber.' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, [], $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId], false);
            }
            LoggerService::info('Preparing patch payload for split payments');
            foreach ($postedResponse['Invoices'][0]['InvoicePaymentSchedules'] as $key => $value) {
                $paymentSplit = $paymentSplits[$key];
                // add discount amount to amount due for the first child payment in sage for balancing the amount
                $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($paymentSplit['due_date'])), $sageRequest->insurerInvoiceDate);
                $dueAmount = roundNumber($paymentSplit['payment_amount'] + ($paymentSplit['sr_no'] == 1 ? $payment->discount_value : 0));

                if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                    $dueDate = $invoicePaymentSchedulesDueDate;
                } else {
                    $dueDate = $paymentSplit['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($paymentSplit['due_date']));
                }

                $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueAmount;
                $postedResponse['Invoices'][0]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
            }

            LoggerService::info('Preparing patch payload for commission splits');

            foreach ($postedResponse['Invoices'][1]['InvoicePaymentSchedules'] as $key => $value) {
                $paymentSplit = $paymentSplits[$key];
                $commissionSplit = $paymentSplit['commission_vat_applicable'];
                $vatOnCommission = $paymentSplit['commission_vat'];

                $dueCommissionSplitAmount = roundNumber(roundNumber($commissionSplit) + roundNumber($vatOnCommission));

                $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($paymentSplit['due_date'])), $sageRequest->insurerInvoiceDate);
                // for upfront and split, due date should always be insurer invoice date for all child payment, for other frequencies, it should be the due date of the first child payment
                if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                    $dueDate = $invoicePaymentSchedulesDueDate;
                } else {
                    $dueDate = $paymentSplit['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($paymentSplit['due_date']));
                }
                $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['AmountDue'] = $dueCommissionSplitAmount;
                $postedResponse['Invoices'][1]['InvoicePaymentSchedules'][$key]['DueDate'] = $dueDate;
            }
            $patchPayload = $postedResponse;
            // 3
            $isLiveApiCallStep3 = true;
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('AR Invoice Premium and Commission non upfront patch already sent');
                $isLiveApiCallStep3 = false;

                $response = $sageLogArray[$stepsMapping['step_2']]['response'] ?? '';
                $postedResponse = ! empty(trim($response)) ? json_decode($response, true) : [];
                // Ensure $postedResponse is always an array
                if (! is_array($postedResponse)) {
                    $postedResponse = [];
                }

            } else {
                LoggerService::info('Sending AR Invoice Premium and Commission non upfront patch to Sage');
                $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                $postedResponse = json_decode($resp, true);
            }
            $postedResponse['endPoint'] = $url;
            $postedResponse['payload'] = $patchPayload;

            if (isset($postedResponse['error'])) {
                LoggerService::info('AR Invoice Premium and Commission non upfront patch failed', extra: ['error' => json_encode($postedResponse['error'])]);
                $postedResponse['sage_request_type'] = SageEnum::SRT_AR_SPPAY_PAY_SCDULE_PATCH;
                $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;
                $errorMessage = 'Error while making ar2 split payments patch to sage';
                $message = 'AR Patch Request failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postedResponse, $postedResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
            if ($isLiveApiCallStep3) {
                $postedResponse['sage_request_type'] = SageEnum::SRT_AR_SPPAY_PAY_SCDULE_PATCH;
                $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;

                $this->logSageApiCall($postedResponse, $postedResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        }

        // 4
        $isLiveApiCallStep4 = true;
        $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr(batchNumber: $batchNumber, type: $sageEntryType, extras: $extraDetails);
        
        if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep4 = false;
            $readyToPostResponse = $sageLogArray[$stepsMapping['step_3']]['response'];
            LoggerService::info('AR Invoice Premium and Commission non upfront ready to post already sent', extra: [
                'BatchNumber' => $batchNumber
            ]);
        } else {
            LoggerService::info('Sending AR Invoice Premium and Commission non upfront ready to post to Sage', extra: [
                'BatchNumber' => $batchNumber
            ]);
            $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making ar2 Apply split payment ready to post to sage';
            $readyToPostArray = json_decode($readyToPostResponse, true);

            if (isset($readyToPostArray['error']['message']['value'])) {
                LoggerService::info('Failed to post AR Invoice Premium and Commission non upfront Ready To Post batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'Error' => $readyToPostArray['error']['message']['value']
                ]);

                $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$batchNumber.')', [], 'GET');
                $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                LoggerService::info('Checking status of AR Invoice Premium and Commission non upfront batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found'
                ]);

                if (isset($arInvoiceBatch['BatchStatus']) && $arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    $this->logSageApiCall($readyToPostInvoiceAr, "", $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    $isAlreadyPosted = true;
                } elseif (! isset($arInvoiceBatch['BatchStatus'])) {
                    LoggerService::info('Failed to get AR Invoice Premium and Commission non upfront batch status', extra: [
                        'BatchNumber' => $batchNumber
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId);
                    $message = 'Failed to get status of AR Invoice Premium and Commission non upfront batch - '.$batchNumber.' failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    LoggerService::info('Error while making AR Invoice Premium and Commission non upfront ready to post to sage', extra: [
                        'BatchNumber' => $batchNumber
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId);
                    $message = 'Failed to post AR Invoice Premium and Commission non upfront Ready To Post batch - '.$batchNumber.' failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                }
            } else {
                if ($isLiveApiCallStep4) {
                    LoggerService::info('Logging AR Invoice Premium and Commission non upfront Ready To Post batch successfully', extra: [
                        'BatchNumber' => $batchNumber
                    ]);
                    $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }
        } else {
            if ($isLiveApiCallStep4) {
                LoggerService::info('Logging AR Invoice Premium and Commission non upfront Ready To Post batch successfully with empty response', extra: [
                    'BatchNumber' => $batchNumber
                ]);
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        }

        // 5
        $isLiveApiCallStep5 = true;
        $aRPostInvoices = SagePayloadFactory::aRPostInvoices(batchNumber: $batchNumber, type: $sageEntryType, extras: $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('AR Invoice Premium and Commission non upfront AR Post already posted');
            $isLiveApiCallStep5 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_4']]['response'], true);
        } else {
            if (($isAlreadyPosted && isset($aRPostInvoices)) || (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_FAIL)) {
                if ($isAlreadyPosted) {
                    LoggerService::info('AR Invoice Premium and Commission non upfront batch already posted', extra : [
                        'BatchNumber' => $batchNumber,
                    ]);
                    $postedResponse = $aRPostInvoices['payload'];
                } else {
                    LoggerService::info('Checking status of AR Invoice Premium and Commission non upfront batch', extra : [
                        'BatchNumber' => $batchNumber
                    ]);
                    $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$batchNumber.')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    LoggerService::info('Status of AR Invoice Premium and Commission non upfront batch details', extra: [
                        'BatchNumber' => $batchNumber,
                        'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found'
                    ]);

                    if (! isset($arInvoiceBatch['BatchStatus'])) {
                        $message = 'NON-Upfront - AR Invoice, Unable to get Batch Status from Sage.';
                        $returnMessage['message'] = $message;
                        $returnMessage['error'] = $message;

                        return $returnMessage;
                    }

                    if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info('AR Invoice Premium and Commission non upfront batch already posted', extra : [
                            'BatchNumber' => $batchNumber
                        ]);
                        $postedResponse = $aRPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('Sending AR Invoice Premium and Commission non upfront AR Post to Sage');
                $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making ar2 Apply split payment Posted to sage';
            $message = 'aRPostInvoices  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        } else {
            LoggerService::info('AR Invoice Premium and Commission non upfront AR Post completed successfully', extra : [
                'BatchNumber' => $batchNumber,
            ]);
            if ($isLiveApiCallStep5) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, $quote, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        }

        LoggerService::info('Completed Non-Upfront AR Invoice Premium and Commission creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Premium and Commission invoice created on sage';

        return $returnMessage;
    }

    private function createAPInvoicePrem($sageRequestDataArray)
    {
        $sageRequestDataArray = array_pad($sageRequestDataArray, 6, []);
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $isPaymentFrequencyUpfront = $extraDetails['reversalFrequency'] ?? ($payment->frequency == PaymentFrequency::UPFRONT);
        if ($isPaymentFrequencyUpfront) {
            return $this->createUpfrontAPInvoicePrem([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        } else {
            return $this->createNonUpfrontAPInvoicePrem([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        }
    }

    private function createUpfrontAPInvoicePrem($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $sageEntryType = $extraDetails['sage_entry_type'] ?? SageEnum::SCT_STRAIGHT;
        $reverseInvoiceDetails = $extraDetails['sageReversalInvoice'] ?? '';
        $userId = $extraDetails['userId'] ?? null;

        $totalSteps = 13;
        $stepsMapping = ['step_1' => 5, 'step_2' => 6, 'step_3' => 7];
        $isReversalPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;

        LoggerService::info('Starting Upfront AP Invoice Premium creation', extra: [
            'entry_type' => $sageEntryType,
        ]);

        switch ($sageEntryType) {
            case SageEnum::SCT_REVERSAL:
                $totalSteps = 22;
                $stepsMapping = [
                    'step_1' => $isReversalPaymentFrequencyUpfront ? 10 : 11,
                    'step_2' => $isReversalPaymentFrequencyUpfront ? 11 : 12,
                    'step_3' => $isReversalPaymentFrequencyUpfront ? 12 : 13,
                ];
                break;
            case SageEnum::SCT_CORRECTION:
                $totalSteps = 22;
                $stepsMapping = ['step_1' => 13, 'step_2' => 14, 'step_3' => 15];
                break;
        }

        $isTotalPriceZero = $payment->total_price == 0;
        if (! $isTotalPriceZero) {
            $isLiveApiCallStep5 = true;
            $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem(request: $sageRequest, type: $sageEntryType, reversalDetails: $reverseInvoiceDetails, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('AP Invoice Premium already sent');
                $isLiveApiCallStep5 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
            } else {
                LoggerService::info('Sending AP Invoice Premium to Sage');
                $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (! empty($postedResponse['BatchNumber'])) {
                LoggerService::info('AP Invoice Premium batch number - '.$postedResponse['BatchNumber']);
                if ($isLiveApiCallStep5) {
                    $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }

                $isLiveApiCallStep6 = true;
                $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType);
                if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                    LoggerService::info('AP Invoice Premium ready to post already sent');
                    $isLiveApiCallStep6 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                } else {
                    LoggerService::info('Sending AP Invoice Premium ready to post to Sage');
                    $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    $errorMessage = 'Error while making AP invoice ready to post to sage';
                    $message = 'readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    LoggerService::info('AP Invoice Premium ready to post completed successfully', extra : [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                    ]);
                    if ($isLiveApiCallStep6) {
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }

                $isLiveApiCallStep7 = true;
                $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber'], type: $sageEntryType);
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                    LoggerService::info('AP Invoice Premium already posted');
                    $isLiveApiCallStep7 = false;
                    $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
                } else {
                    $isAlreadyPosted = false;
                    if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                        LoggerService::info('Checking status of AP Invoice Premium batch', extra : [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                        ]);
                        $aPInvoiceBatch = $this->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                        $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                        LoggerService::info('Status of AP Invoice Premium batch', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                            'BatchStatus' => $aPInvoiceBatch['BatchStatus'] ?? 'Not found',
                        ]);
                        if (! isset($aPInvoiceBatch['BatchStatus'])) {
                            $message = 'Upfront - AP Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;

                            return $returnMessage;
                        }

                        if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('AP Invoice Premium batch already posted', extra : [
                                'BatchNumber' => $postedResponse['BatchNumber'],
                            ]);
                            $postedResponse = $aPPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }

                    if (! $isAlreadyPosted) {
                        LoggerService::info('Sending AP Invoice Premium post to Sage');
                        $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                if (isset($postedResponse['error'])) {
                    $errorMessage = 'Error while making AP invoices Posted to sage';
                    $message = 'aPPostInvoices failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    LoggerService::info('AP Invoice Premium AP Post completed successfully', extra : [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                    ]);
                    if ($isLiveApiCallStep7) {
                        $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }
            } else {
                $errorMessage = 'Ap invoice prem failed from sage';
                $message = 'createAPInvoicePrem  failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
        } else {
            LoggerService::info('Skipping AP Invoice Premium creation due to zero pricing');
        }

        LoggerService::info('Completed Upfront AP Invoice Premium creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Premium invoice created on sage';

        return $returnMessage;
    }

    private function createNonUpfrontAPInvoicePrem($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $sageEntryType = $extraDetails['sage_entry_type'] ?? SageEnum::SCT_STRAIGHT;
        $reverseInvoiceDetails = $extraDetails['sageReversalInvoice'] ?? '';
        $userId = $extraDetails['userId'] ?? null;

        $totalSteps = 15;
        $stepsMapping = ['step_1' => 6, 'step_2' => 7, 'step_3' => 8, 'step_4' => 9];

        LoggerService::info('Starting Non-Upfront AP Invoice Premium creation', extra: [
            'entry_type' => $sageEntryType,
        ]);

        if ($sageEntryType == SageEnum::SCT_CORRECTION) {
            $totalSteps = 25;
            $stepsMapping = ['step_1' => 14, 'step_2' => 15, 'step_3' => 16, 'step_4' => 17];
        }

        $isLiveApiCallStep6 = true;
        $createAPInvoicePrem = SagePayloadFactory::createAPInvoiceSplitPayments($sageRequest, $paymentSplits, $sageEntryType, $reverseInvoiceDetails, $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('AP Invoice Premium non upfront already sent');
            $isLiveApiCallStep6 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('Sending AP Invoice Premium non upfront to Sage');
            $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            $apBatchNumber = $postedResponse['BatchNumber'];
            $url = 'AP/APInvoiceBatches('.$apBatchNumber.')';
            LoggerService::info('AP Invoice Split Payments batch number - '.$apBatchNumber);
            if ($isLiveApiCallStep6) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }

            if ($sageEntryType != SageEnum::SCT_REVERSAL) {
                $isLiveApiCallStep7 = true;
                if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                    LoggerService::info('AP Invoice Premium non upfront patch already sent');
                    $isLiveApiCallStep7 = false;

                    $response = $sageLogArray[$stepsMapping['step_2']]['response'] ?? '';
                    $postedResponse = ! empty(trim($response)) ? json_decode($response, true) : [];
                    // Ensure $postedResponse is always an array
                    if (! is_array($postedResponse)) {
                        $postedResponse = [];
                    }

                    $aPInvoicePaymentsSchedule = $postedResponse['payload'];
                    $resp = $postedResponse['response'] ?? [];
                } else {
                    LoggerService::info('Preparing patch payload for AP Invoice Premium non upfront');
                    $aPInvoicePaymentsScheduleResponse = (new SageCustomApiService)->getAPInvoicePaymentScheduleByBatchNumber($postedResponse['BatchNumber']);

                    if ($aPInvoicePaymentsScheduleResponse['status']) {
                        $aPInvoicePaymentsSchedule = $aPInvoicePaymentsScheduleResponse['response'];
                        foreach ($aPInvoicePaymentsSchedule as $key => $aPInvoicePaymentSchedule) {
                            // add discount amount to amount due for the first child payment in sage for balancing the amount
                            $dueAmount = roundNumber($paymentSplits[$key]['payment_amount'] + ($paymentSplits[$key]['sr_no'] == 1 ? $payment->discount_value : 0));
                            $invoicePaymentSchedulesDueDate = SagePayloadFactory::calculateDueDate(date('Y-m-d', strtotime($paymentSplits[$key]['due_date'])), $sageRequest->insurerInvoiceDate);
                            if ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
                                $dueDate = $invoicePaymentSchedulesDueDate;
                            } else {
                                $dueDate = $paymentSplits[$key]['sr_no'] == 1 ? $invoicePaymentSchedulesDueDate : date('Y-m-d', strtotime($paymentSplits[$key]['due_date']));
                            }

                            $aPInvoicePaymentSchedule->datedue = Carbon::parse($dueDate)->format(env('SAGE_300_CUSTOM_API_DATE_FORMAT'));
                            $aPInvoicePaymentSchedule->amtdue = $dueAmount;
                            $aPInvoicePaymentSchedule->amtduehc = $dueAmount;
                            $aPInvoicePaymentSchedule->audtorg = $this->sageDBName;
                        }
                    } else {
                        $errorMessage = 'Error while getting split payment schedule from sage';
                        $message = $aPInvoicePaymentsScheduleResponse['error'];

                        return $this->logErrorAndReturn([$quote, $message, $errorMessage, [], [], $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId], false);
                    }

                    LoggerService::info('Sending AP Invoice Premium non upfront patch to Sage');
                    $resp = (new SageCustomApiService)->updateAPInvoicePaymentSchedule($postedResponse['BatchNumber'], $aPInvoicePaymentsSchedule);
                    $postedResponse['response'] = $resp;
                }

                $postedResponse['endPoint'] = $resp['url'] ?? $postedResponse['endPoint'] ?? null;
                $postedResponse['payload'] = $aPInvoicePaymentsSchedule;
                if (! $postedResponse['response']['status']) {
                    LoggerService::info('AP Invoice Premium non upfront patch failed', extra: ['data' => json_encode($postedResponse['response'])]);
                    $postedResponse['sage_request_type'] = SageEnum::SRT_AP_SPPAY_PAY_SCDULE_PATCH;
                    $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;
                    $errorMessage = 'Error while making AP split payments patch to sage';
                    $message = 'AP Patch Request failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postedResponse, $resp, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                }
                LoggerService::info('AP Invoice Premium non upfront patch completed successfully');
                if ($isLiveApiCallStep7) {
                    $postedResponse['sage_request_type'] = SageEnum::SRT_AP_SPPAY_PAY_SCDULE_PATCH;
                    $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;

                    $this->logSageApiCall($postedResponse, $postedResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

            $isLiveApiCallStep8 = true;
            $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP(batchNumber: $apBatchNumber, type: $sageEntryType);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('AP Invoice Premium non upfront ready to post already sent');
                $isLiveApiCallStep8 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                LoggerService::info('Sending AP Invoice Premium non upfront ready to post to Sage');
                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making AP invoice ready to post to sage';
                $message = 'readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            } else {
                LoggerService::info('AP Invoice Premium non upfront ready to post completed successfully', extra : [
                    'BatchNumber' => $postedResponse['BatchNumber'],
                ]);
                if ($isLiveApiCallStep8) {
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

            $isLiveApiCallStep9 = true;
            $aPPostInvoices = SagePayloadFactory::aPPostInvoices(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType);
            if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('AP Invoice Premium non upfront AP Post already posted');
                $isLiveApiCallStep9 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_4']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_FAIL) {
                    LoggerService::info('Checking status of AP Invoice Premium non upfront batch', extra : [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                    ]);
                    $aPInvoiceBatch = $this->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    LoggerService::info('Status of AP Invoice Split Payments batch', extra: [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                        'BatchStatus' => $aPInvoiceBatch['BatchStatus'] ?? 'Not found',
                    ]);
                    if (! isset($aPInvoiceBatch['BatchStatus'])) {
                        $message = 'NON Upfront - AP Invoice, Unable to get Batch Status from Sage.';
                        $returnMessage['message'] = $message;
                        $returnMessage['error'] = $message;

                        return $returnMessage;
                    }
                    if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        LoggerService::info('AP Invoice Premium non upfront batch already posted', extra : [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                        ]);
                        $postedResponse = $aPPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    LoggerService::info('Sending AP Invoice Premium non upfront AP Post to Sage');
                    $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making AP invoices Posted to sage';
                $message = 'aPPostInvoices failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            } else {
                LoggerService::info('AP Invoice Premium non upfront AP Post completed successfully', extra : [
                    'BatchNumber' => $postedResponse['BatchNumber'],
                ]);
                if ($isLiveApiCallStep9) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, $quote, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

        } else {
            $errorMessage = 'Ap invoice prem failed from sage';
            $message = 'createAPInvoicePrem  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }

        LoggerService::info('Completed Non-Upfront AP Invoice Premium creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Premium invoice created on sage';

        return $returnMessage;
    }

    public function createARInvoiceDis($sageRequestDataArray)
    {
        $sageRequestDataArray = array_pad($sageRequestDataArray, 4, []);
        [$sageRequest, $quote, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $isReversalDiscount = isset($extraDetails['is_reversal_discount']) ?? false;
        $isOnlyCorrection = isset($extraDetails['is_only_correction']) ?? false;
        $userId = $extraDetails['userId'] ?? null;
        $isDiscountApplied = $sageRequest->discount > 0;

        $sageEntryType = $extraDetails['sage_entry_type'] ?? SageEnum::SCT_STRAIGHT;
        $reverseInvoiceDetails = $extraDetails['sageReversalInvoice'] ?? '';
        $isPaymentFrequencyUpfront = $extraDetails['paymentFrequency'] ?? true;

        $totalSteps = 15;
        $stepsMapping = ['step_1' => 10, 'step_2' => 11, 'step_3' => 12];

        LoggerService::info('Starting AR Invoice Discount creation', extra: [
            'entry_type' => $sageEntryType,
            'is_reversal_discount' => $isReversalDiscount,
            'discount_applied' => $isDiscountApplied,
        ]);

        switch ($sageEntryType) {
            case SageEnum::SCT_REVERSAL:
                $totalSteps = 22;
                $stepsMapping = [
                    'step_1' => $isPaymentFrequencyUpfront ? 17 : 19,
                    'step_2' => $isPaymentFrequencyUpfront ? 18 : 20,
                    'step_3' => $isPaymentFrequencyUpfront ? 19 : 21,
                ];
                break;
            case SageEnum::SCT_CORRECTION:
                $totalSteps = 22;
                $stepsMapping = [
                    'step_1' => $isPaymentFrequencyUpfront ? 20 : 22,
                    'step_2' => $isPaymentFrequencyUpfront ? 21 : 23,
                    'step_3' => $isPaymentFrequencyUpfront ? 22 : 24,
                ];
                break;
        }

        if ($isOnlyCorrection) {
            $totalSteps = 22;
            $stepsMapping = [
                'step_1' => $isPaymentFrequencyUpfront ? 17 : 19,
                'step_2' => $isPaymentFrequencyUpfront ? 18 : 20,
                'step_3' => $isPaymentFrequencyUpfront ? 19 : 21,
            ];
        }

        /* createARInvoiceDis */
        if ($isDiscountApplied || $isReversalDiscount) {
            $isLiveApiCallStep10 = true;
            $createARInvoiceDis = SagePayloadFactory::createARInvoiceDis(request: $sageRequest, type: $sageEntryType, reversalDetails: $reverseInvoiceDetails, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('AR Invoice Discount already sent');
                $isLiveApiCallStep10 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
            } else {
                LoggerService::info('Sending AR Invoice Discount to Sage');
                $resp = $this->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (! empty($postedResponse['BatchNumber'])) {
                LoggerService::info('AR Invoice Discount batch number - '.$postedResponse['BatchNumber']);
                if ($isLiveApiCallStep10) {
                    $this->logSageApiCall($createARInvoiceDis, $postedResponse, $quote, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }

                $isLiveApiCallStep11 = true;
                $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
                if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                    LoggerService::info('AR Invoice Discount ready to post already sent');
                    $isLiveApiCallStep11 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                } else {
                    LoggerService::info('Sending AR Invoice Discount ready to post to Sage');
                    $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    $errorMessage = 'Error while making Ar discount invoice ready to post to sage';
                    $message = 'readyToPostInvoiceAr failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    LoggerService::info('AR Invoice Discount ready to post completed successfully', extra : [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                    ]);
                    if ($isLiveApiCallStep11) {
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }

                $isLiveApiCallStep12 = true;
                $aRPostInvoices = SagePayloadFactory::aRPostInvoices(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                    LoggerService::info('AR Invoice Discount AR Post already posted');
                    $isLiveApiCallStep12 = false;
                    $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
                } else {
                    $isAlreadyPosted = false;
                    if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                        LoggerService::info('Checking status of AR Invoice Discount batch', extra : [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                        ]);
                        $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                        $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                        LoggerService::info('Status of AR Invoice Discount batch', extra: [
                            'BatchNumber' => $postedResponse['BatchNumber'],
                            'BatchStatus' => $arInvoiceBatch['BatchStatus'] ?? 'Not found',
                        ]);
                        if (! isset($arInvoiceBatch['BatchStatus'])) {
                            $message = 'AR Discount Invoice, Unable to get Batch Status from Sage.';
                            $returnMessage['message'] = $message;
                            $returnMessage['error'] = $message;

                            return $returnMessage;
                        }
                        if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            LoggerService::info('AR Invoice Discount AR Post already posted', extra : [
                                'BatchNumber' => $postedResponse['BatchNumber'],
                            ]);
                            $postedResponse = $aRPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }

                    if (! $isAlreadyPosted) {
                        LoggerService::info('Sending AR Invoice Discount AR Post to Sage');
                        $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                if (isset($postedResponse['error'])) {
                    $errorMessage = 'Error while making Ar discount invoice Posted to sage';
                    $message = ' aRPostInvoices failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    LoggerService::info('AR Invoice Discount AR Post completed successfully', extra : [
                        'BatchNumber' => $postedResponse['BatchNumber'],
                    ]);
                    if ($isLiveApiCallStep12) {
                        $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }
            } else {
                $errorMessage = 'Ar discount invoice failed from sage';
                $message = ' createARInvoiceDis failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createARInvoiceDis, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
        }

        LoggerService::info('Completed AR Invoice Discount creation successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Discount invoice created on sage';

        return $returnMessage;
    }

    public function applyPaymentARInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $isTotalPriceZero = $payment->total_price == 0;

        /* Start: Temporary code for historic data to allow book policy after m2 launch */
        $isQuoteFallUnderSkippableCriteria = $this->skipApplyPrepaymentsForSpecificLeads($quote, $payment, $paymentSplits);
        if ($isQuoteFallUnderSkippableCriteria['status']) {
            return $isQuoteFallUnderSkippableCriteria;
        }
        /* End: Temporary code for historic data to allow book policy after m2 launch */

        /* applyPaymentARInvoices */
        $isTransactionPaidAndFrequencyUpfront = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::UPFRONT;

        if ($isTransactionPaidAndFrequencyUpfront && ! $isTotalPriceZero) {
            return $this->applyUpfrontPaymentARInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        } elseif ($isTotalPriceZero) {
            LoggerService::info('Apply Upfront Payment AR Invoice skipped due to zero price');
        }

        $isFrequencySplitAndFirstChildPaymentPaid = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

        if ($isFrequencySplitAndFirstChildPaymentPaid) {
            return $this->applySplitPaymentARInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        }

        $isFrequencyUpfrontOrSplit = in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]);
        $isFirstPaymentPaidOrCaptured = in_array($paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
        if (! $isFrequencyUpfrontOrSplit && $isFirstPaymentPaidOrCaptured) {
            return $this->applyNonSplitNonUpfrontPaymentARInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails]);
        }

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Apply Prepayment completed';

        return $returnMessage;

    }

    private function applyUpfrontPaymentARInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $totalSteps = 15;
        // 13
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        $sageEntryType = $extraDetails['sage_entry_type'] ?? SageEnum::SCT_STRAIGHT;

        LoggerService::info('Starting Apply Upfront Payment AR Invoice', extra: [
            'entry_type' => $sageEntryType,
        ]);

        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Payment Receipt already sent');
            $isLiveApiCallStep13 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending Apply Payment Receipt to Sage');
            //            TODO:: This need to be defined in top but isPostAllSplitPayment (Last param) need to be handled
            $payLoadOptions = SagePayloadFactory::createPaymentReceiptOneInvoice($quote, $sageRequest->customerId, $payment, $paymentSplits, true);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making split prepayments to sage';
            $message = 'createPaymentReceiptOneInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        LoggerService::info('Apply Payment Receipt batch number - '.$batchNumber);
        // 14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr(batchNumber: $batchNumber, type: $sageEntryType, extras: $extraDetails);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Payment Receipt ready to post already sent');
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending Payment Receipt ready to post to Sage');
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptAr failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            LoggerService::info('Payment Receipt ready to post completed successfully', extra : [
                'BatchNumber' => $batchNumber,
            ]);
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $quote, $currentStep, $totalSteps);
            }
        }

        // 15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        $aRPostReceipts = SagePayloadFactory::aRPostReceipts(batchNumber: $batchNumber, type: $sageEntryType, extras: $extraDetails);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Payment Receipt AR post already sent');
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('Checking status of Payment Receipt batch', extra : [
                    'BatchNumber' => $batchNumber,
                ]);
                $aRReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                LoggerService::info('Status of Payment Receipt batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'BatchStatus' => $aRReceiptBatch['BatchStatus'] ?? 'Not found',
                ]);
                if (! isset($aRReceiptBatch['BatchStatus'])) {
                    $message = 'Apply Upfront PrePayment, Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('Payment Receipt AR post already posted', extra : [
                        'BatchNumber' => $batchNumber,
                    ]);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('Sending Payment Receipt AR post to Sage');
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = 'aRPostReceipts failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        LoggerService::info('Payment Receipt AR post completed successfully', extra : [
            'BatchNumber' => $batchNumber,
        ]);
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }

        LoggerService::info('Completed Apply Upfront Payment AR Invoice successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }

    private function applySplitPaymentARInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 15;

        // 12
        $currentStep = 13;
        $isLiveApiCallStep13 = true;

        LoggerService::info('Starting Apply Non Upfront Payment AR Invoice');

        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Non Upfront Payment AR Invoice already sent');
            $isLiveApiCallStep13 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending Apply Non Upfront Payment AR Invoice to Sage');
            //            TODO:: This need to be defined in top but isPostAllSplitPayment (Last param) need to be handled
            $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageRequest->customerId, $payment, $paymentSplits, true);
            $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
            $response = json_decode($resp, true);
        }

        if (isset($response['error'])) {
            $errorMessage = 'Error while making Apply split prepayments to sage';
            $message = ' arSplitPrepaymentPayload failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $response, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $quote, $currentStep, $totalSteps);
        }

        $batchNumber = $response['BatchNumber'];
        LoggerService::info('Apply Non Upfront Payment AR Invoice batch number - '.$batchNumber);
        // 14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Non Upfront Payment AR Invoice ready to post already sent');
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending Apply Non Upfront Payment AR Invoice ready to post to Sage');
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = ' readyToPostReceiptAr - BatchNumber : '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            LoggerService::info('Apply Non Upfront Payment AR Invoice ready to post completed successfully', extra : [
                'BatchNumber' => $batchNumber,
            ]);
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $quote, $currentStep, $totalSteps);
            }
        }

        // 15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        $aRPostReceipts = SagePayloadFactory::aRPostReceipts(batchNumber: $batchNumber, extras: $extraDetails);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Non Upfront Payment AR Invoice already sent');
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {

            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('Checking status of Apply Non Upfront Payment AR Invoice batch', extra : [
                    'BatchNumber' => $batchNumber,
                ]);
                $aRReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                LoggerService::info('Status of Apply Non Upfront Payment AR Invoice batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'BatchStatus' => $aRReceiptBatch['BatchStatus'] ?? 'Not found',
                ]);
                if (! isset($aRReceiptBatch['BatchStatus'])) {
                    $message = 'Apply Split PrePayment, Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('Apply Non Upfront Payment AR Invoice batch already posted', extra : [
                        'BatchNumber' => $batchNumber,
                    ]);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('Sending Apply Non Upfront Payment AR Invoice post to Sage');
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = ' aRPostReceipts failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        LoggerService::info('Apply Non Upfront Payment AR Invoice post completed successfully', extra : [
            'BatchNumber' => $batchNumber,
        ]);
        //            TODO:: This undefined variable need to defined properly
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }

        LoggerService::info('Completed Apply Non Upfront Payment AR Invoice successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }

    private function applyNonSplitNonUpfrontPaymentARInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray, $extraDetails] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        $totalSteps = 18;

        // 15
        $currentStep = 16;
        $isLiveApiCallStep16 = true;

        LoggerService::info('Starting Apply Non Split Non Upfront Payment AR Invoice');

        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice already sent');
            $isLiveApiCallStep16 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending Apply Non Split Non Upfront Payment AR Invoice to Sage');
            $readyToPostReceiptAr = SagePayloadFactory::arSplitPrepaymentPayload($quote, $sageRequest->customerId, $payment, $paymentSplits, false);
            $resp = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'POST');
            $response = json_decode($resp, true);
        }

        if (isset($response['error'])) {
            $errorMessage = 'Error while making Apply split prepayments to sage';
            $message = 'arSplitPrepaymentPayload failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $response, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        if ($isLiveApiCallStep16) {
            $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $quote, $currentStep, $totalSteps);

        }
        $batchNumber = $response['BatchNumber'];
        LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice batch number - '.$batchNumber);
        // 16
        $currentStep = 17;
        $isLiveApiCallStep17 = true;
        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr(batchNumber: $batchNumber, extras: $extraDetails);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice ready to post already sent');
            $isLiveApiCallStep17 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('Sending Apply Non Split Non Upfront Payment AR Invoice ready to post to Sage');
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptAr  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice ready to post completed successfully', extra : [
                'BatchNumber' => $batchNumber,
            ]);
            if ($isLiveApiCallStep17) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $quote, $currentStep, $totalSteps);
            }
        }

        // 17
        $currentStep = 18;
        $isLiveApiCallStep18 = true;
        $aRPostReceipts = SagePayloadFactory::aRPostReceipts(batchNumber: $batchNumber, extras: $extraDetails);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice already sent');
            $isLiveApiCallStep18 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('Checking status of Apply Non Split Non Upfront Payment AR Invoice batch', extra : [
                    'BatchNumber' => $batchNumber,
                ]);
                $aRReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                LoggerService::info('Status of Apply Non Split Non Upfront Payment AR Invoice batch', extra: [
                    'BatchNumber' => $batchNumber,
                    'BatchStatus' => $aRReceiptBatch['BatchStatus'] ?? 'Not found',
                ]);
                if (! isset($aRReceiptBatch['BatchStatus'])) {
                    $message = 'Apply Non Split Non Upfront Payment , Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice batch already posted', extra : [
                        'BatchNumber' => $batchNumber,
                    ]);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('Sending Apply Non Split Non Upfront Payment AR Invoice post to Sage');
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = 'aRPostReceipts - BatchNumber '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        if ($isLiveApiCallStep18) {
            //            TODO:: $postedResponse need to defined properly
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }
        LoggerService::info('Apply Non Split Non Upfront Payment AR Invoice post completed successfully', extra : [
            'BatchNumber' => $batchNumber,
        ]);

        LoggerService::info('Completed Apply Non Split Non Upfront Payment AR Invoice successfully');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }

    public function logErrorAndReturn($logDataArray, $storeSageApiLog = true): array
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $logDataArray = array_pad($logDataArray, 9, null);
        [$quote, $message, $errorMessage, $payload, $response, $currentStep, $totalSteps, $status, $userId] = $logDataArray;

        LoggerService::info(self::class.' fn: '.__FUNCTION__." - SAGE API: $quote->code - $message");
        LoggerService::info(self::class.' fn: '.__FUNCTION__." - SAGE API: $quote->code - $errorMessage");

        $sageErrorMessageOnSuccess = $response['Message'] ?? null;

        $returnMessage['message'] = $errorMessage;
        $responseArray = $this->convertResponseToArray($response);
        $sageErrorMessage = $responseArray['error']['message']['value'] ?? $responseArray['error'] ?? $sageErrorMessageOnSuccess ?? null;
        LoggerService::info(self::class.' fn: '.__FUNCTION__." - SAGE API: $quote->code - ".json_encode($sageErrorMessage));
        $returnMessage['error'] = $sageErrorMessage;
        if ($this->sageHasProcessingConflict($sageErrorMessage)) {
            $returnMessage['message'] = SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE;
        }

        if ($storeSageApiLog) {
            $this->logSageApiCall($payload, $response, $quote, $quote, $currentStep, $totalSteps, $status, $userId);
        }

        return $returnMessage;
    }

    public function convertResponseToArray($response)
    {
        if (is_array($response)) {
            return $response;
        }

        return json_decode($response, true);
    }

    public function skipApplyPrepaymentsForSpecificLeads($quote, $payment, $paymentSplits)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $startDate = Carbon::parse('2024-04-23')->startOfDay();
        $endDate = Carbon::parse('2024-08-15')->endOfDay();
        $quoteCreatedAt = Carbon::parse($quote->created_at);

        $isQuoteCreatedWithInDateRange = $quoteCreatedAt->between($startDate, $endDate);
        $isPaymentMethodCreditApproval = $payment->payment_methods_code == PaymentMethodsEnum::CreditApproval;
        $isPaymentFrequencySplitPayment = $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

        if ($isQuoteCreatedWithInDateRange && ! $isPaymentMethodCreditApproval) {
            if ($isPaymentFrequencySplitPayment) {
                $paymentSplitsWithNoSageReceipt = $paymentSplits->whereNull('sage_reciept_id')->count();
                if ($paymentSplitsWithNoSageReceipt) {
                    LoggerService::info('applyUpfrontPaymentARInvoices skipped due to sage receipt not generated on sage');
                    $returnMessage['status'] = true;
                    $returnMessage['message'] = 'Apply Prepayment skipped due to sage receipt not generated on sage';

                    return $returnMessage;
                }

                return $returnMessage;
            } else {
                $firstPaymentSplitWithNoSageReceipt = $paymentSplits->where('sr_no', 1)->whereNull('sage_reciept_id')->count();
                if ($firstPaymentSplitWithNoSageReceipt) {
                    LoggerService::info('applyUpfrontPaymentARInvoices skipped due to sage receipt not generated on sage');
                    $returnMessage['status'] = true;
                    $returnMessage['message'] = 'Apply Prepayment skipped due to sage receipt not generated on sage';

                    return $returnMessage;
                }
            }

            return $returnMessage;
        }

        return $returnMessage;
    }

    public function updateSageProcessStatus($sageProcess, $status, $message = null, $logFor = 'Policy Book')
    {
        $sageProcessData['status'] = $status;
        if ($message) {
            $sageProcessData['message'] = json_encode(['message' => $message]);
        }

        $sageProcess->update($sageProcessData);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - '.$logFor.': updateSageProcessStatus - ID: '.$sageProcess->id.' - Status: '.$status);
    }

    public function updateAndLogQuoteStatus($quote, $quoteTypeId, $quoteStatusId, $userId)
    {
        $latestQuoteStatusLog = QuoteStatusLog::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->id,
        ])->latest()->first();

        unset($quote->userId);

        $previousQuoteStatusId = $quote->quote_status_id;
        $newQuoteStatusId = $quoteStatusId;

        $quoteData = [
            'quote_status_id' => $newQuoteStatusId,
            'quote_status_date' => now(),
        ];

        if (in_array($quoteTypeId, [QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Pet, QuoteTypeId::Cycle, QuoteTypeId::Yacht, QuoteTypeId::Business])) {
            $quoteData['stale_at'] = null;
        }
        if ($newQuoteStatusId == QuoteStatusEnum::PolicyBooked) {
            $quoteData['policy_booking_date'] = Carbon::now();
        }

        $quote->update($quoteData);

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy Book: updateAndLogQuoteStatus - Code: '.$quote->code.' - Status: '.$newQuoteStatusId);

        $quoteLogData = [
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quote->id,
            'current_quote_status_id' => $newQuoteStatusId,
            'previous_quote_status_id' => $previousQuoteStatusId,
            'created_by' => $userId,
        ];

        $isQuoteLogSameAsBefore = $latestQuoteStatusLog?->current_quote_status_id == QuoteStatusEnum::PolicyBooked && $latestQuoteStatusLog?->previous_quote_status_id == $previousQuoteStatusId;
        // check if the last quote log status is same as new status then update the same log
        if ($latestQuoteStatusLog && $isQuoteLogSameAsBefore) {
            $latestQuoteStatusLog->update($quoteLogData);
        } else {
            QuoteStatusLog::create($quoteLogData);
        }

        if (in_array($quote->quote_status_id, [QuoteStatusEnum::PolicyBooked, QuoteStatusEnum::POLICY_BOOKING_FAILED])) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy Book: updateAndLogQuoteStatus - Code: '.$quote->code.' - Start updateStatusesAndAllocate - Quote Status ID: '.$quote->quote_status_id);
            $this->updateStatusesAndAllocate($quote, $quoteTypeId);
        }

    }

    public function createSageProcess($quote, $sageRequest, $request)
    {
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
            'model_type' => $quote::class,
            'model_id' => $quote->id,
        ])->first();

        if ($sageProcess) {
            if ($sageProcess->status == SageEnum::SAGE_PROCESS_FAILED_STATUS) {
                $sageProcess->update($sageProcessData);
            }
        } else {
            $sageProcessData['model_type'] = $quote::class;
            $sageProcessData['model_id'] = $quote->id;
            SageProcess::create($sageProcessData);
        }
    }

    public function sageHasProcessingConflict($sageErrorMessage)
    {
        $sageErrorMessage = strtolower($sageErrorMessage);

        return str_contains($sageErrorMessage, 'processing conflict') || str_contains($sageErrorMessage, 'post in progress') || str_contains($sageErrorMessage, 'record already exists');
    }

    public function scheduleSageProcesses($insurerId = null): void
    {
        LoggerService::info('Starting sage process scheduling');

        $processLockKey = SageEnum::SAGE_PROCESS_LOCK_KEY;
        $status[] = SageEnum::SAGE_PROCESS_PENDING_STATUS;
        if ((new SageApiService)->isSageRetryTimeoutEnabled()) {
            $status[] = SageEnum::SAGE_PROCESS_TIMEOUT_STATUS;
        }
        $sageProcessCommandLock = Cache::lock($processLockKey, 20);
        if ($sageProcessCommandLock->get()) {
            $sageProcesses = SageProcess::whereIn('status', $status)
                ->whereNotIn('insurance_provider_id', function ($query) {
                    $query->select('insurance_provider_id')
                        ->from('sage_processes')
                        ->where('status', SageEnum::SAGE_PROCESS_PROCESSING_STATUS);
                })->when($insurerId, function ($query) use ($insurerId) {
                    $query->where('insurance_provider_id', $insurerId);
                })->orderBy('created_at')
                ->groupBy('insurance_provider_id')
                ->get();

            if (count($sageProcesses) > 0) {
                foreach ($sageProcesses as $sageProcess) {

                    LoggerService::info('Processing sage process for Insurance Provider', extra: [
                        'SageProcessID' => $sageProcess?->id,
                        'InsuranceProviderID' => $sageProcess?->insurance_provider_id,
                    ]);

                    $sageProcessRequest = json_decode($sageProcess->request);
                    $sageRequest = $sageProcessRequest->sagePayload;
                    $request = $sageProcessRequest->requestPayload;

                    if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                        $quote = $this->getQuoteObject($request->model_type, $sageProcess->model_id);
                        BookPolicyOnSageJob::dispatch($sageRequest, $quote, $request, $sageProcess)->onQueue('insly');
                    } elseif ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST) {
                        $model = $sageProcess->model;
                        SendUpdateSageJob::dispatch($request, $model, $sageRequest, $sageProcess)->onQueue('insly');
                    } elseif ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_POST_PREPAYMENT_REQUEST) {
                        PostPrepaymentToSageJob::dispatch($request, $sageRequest, $sageProcess)->onQueue('insly');
                    } elseif ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_EMBEDDED_PRODUCT_REQUEST) {
                        $ePTransaction = $sageProcess->model;
                        BookEmbeddedProductOnSageJob::dispatch($sageRequest, $ePTransaction, $request, $sageProcess)->onQueue('insly');
                    }
                }
            } else {
                LoggerService::info('No eligible Sage processes found for scheduling or all processes are currently being processed for each insurance provider');
            }
            $sageProcessCommandLock->release();
        } else {
            LoggerService::info('Sage policy or endorsement booking job is already running. Skipping execution.');
        }
    }

    public function updateStatusesAndAllocate($quote, $quoteTypeId)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy Book: Quote '.$quote?->code.' - start');

        $policyIssuanceAutomation = $quote?->policyIssuance;

        if ($policyIssuanceAutomation) {
            $quoteType = $policyIssuanceAutomation->quote_type;
            $insuranceProvider = $policyIssuanceAutomation->insuranceProvider;
            $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider?->code);

            /* if the Policy Issuance exist for the Insurer and LOB than assign the Advisor */
            if ($insuranceProviderAutomation) {
                LoggerService::info('Policy Book : Quote '.$quote?->code.' : '.__FUNCTION__.' - assign advisor and update insurer and api issuance status of quote');
                if ($quoteType === QuoteTypes::CAR->value && in_array($insuranceProvider->code, [InsuranceProvidersEnum::RSA, InsuranceProvidersEnum::AXA])) {
                    app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, $quoteType);
                } else {
                    // TODO:: This should be updated with the new function in PolicyIssuanceService
                    $insuranceProviderAutomation?->updateQuoteApiIssuanceStatusAndAllocate($quote);
                }
            } else {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy Book: Quote '.$quote?->code.' - Insurer: '.$insuranceProvider?->code.' automation class not found');
            }
        } else {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy Book: Quote '.$quote?->code.' - policy issuance automation not found');
        }

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Policy Book: Quote '.$quote?->code.' - end');
    }

    public function handleSplitPaymentApproval($quoteTypeId, $quote, $payment, $paymentSplits)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Split payment approval process started - process called from SageApiService - QuoteCode: '.$quote->code.' - PaymentCode: '.$payment->code);
        $modelType = $payment->send_update_log_id ? $quoteTypeId : QuoteTypes::getName($quoteTypeId)->value;
        $collectionAmount = $paymentSplits->pluck('premium_authorized', 'sr_no')->toArray();
        $splitPaymentApprovalRequest = new SplitPaymentApproveRequest([
            'modelType' => $modelType,
            'quote_id' => $quote->id,
            'plan_id' => $payment->plan_id,
            'payment_code' => $payment->code,
            'customer_id' => $quote->customer_id,
            'collection_amount' => $collectionAmount,
            'is_declined' => 0,
            'is_capture' => 1,
            'is_approved' => 0,
            'declined_reason' => $payment->declined_reason,
            'send_update_id' => $payment->send_update_log_id ?? null,
            'collection_type' => $payment->collection_type,
        ]);

        $response = app(PaymentRepository::class)->handlePaymentApprove($splitPaymentApprovalRequest);
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Split payment approval process completed - process called from SageApiService - QuoteCode: '.$quote->code.' - PaymentCode: '.$payment->code);

        return $response;
    }

    public function isSageEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);
    }

    public function isSageRetryTimeoutEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_TIMEOUT_RETRY_ENABLED);
    }

    public function isPaymentPaidOrCreditApproved($payment, $paymentSplits, $sageRequest)
    {
        $isPaymentCreditApproval = $payment->payment_methods_code == PaymentMethodsEnum::CreditApproval;
        $isTransactionPaidAndFrequencyUpfront = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::UPFRONT;
        $isFrequencySplitAndFirstChildPaymentPaid = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;
        $isFrequencyUpfrontOrSplit = in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]);
        $isFirstPaymentPaidOrCaptured = in_array($paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);

        return $isPaymentCreditApproval || $isTransactionPaidAndFrequencyUpfront || $isFrequencySplitAndFirstChildPaymentPaid || (! $isFrequencyUpfrontOrSplit && $isFirstPaymentPaidOrCaptured);
    }

    public function schedulePostPrepaymentToSageProcess($data)
    {

        $response = ['status' => false, 'message' => null, 'errors' => [], 'data' => null];
        [$quote, $quoteType, $paymentSplit, $sendUpdateLog] = $data;
        $paymentSplit = $paymentSplit->refresh();
        $quote = $quote->refresh();
        $sendUpdateLog = $sendUpdateLog?->refresh();

        LoggerService::info('Scheduling sage process for prepayment posting for Payment Split', extra: [
            'PaymentSplitID' => $paymentSplit?->id,
            'PaymentSplitCode' => $paymentSplit?->code,
        ]);

        $preChecksForPostingPrepaymentOnSage = (new SageApiService)->preChecksForPostPrepaymentSchedule($quote, $sendUpdateLog, $paymentSplit);

        if (! $preChecksForPostingPrepaymentOnSage['status']) {
            $response['errors'] = $preChecksForPostingPrepaymentOnSage['errors'];
            LoggerService::info('Error while scheduling sage process for prepayment posting for Payment Split', extra: [
                'errors' => $response['errors'],
                'PaymentSplitCode' => $paymentSplit?->code,
            ]);

            return $response;
        }

        $payment = $paymentSplit->payment;
        $insurer = getInsuranceProvider($payment, $quoteType);
        $sagePayload = [
            'userId' => auth()->id(),
            'insurerID' => $insurer?->id,
            'quoteCode' => $quote->code,
            'quoteType' => $quoteType,
            'paymentSplitId' => $paymentSplit->id,
            'sendUpdateId' => $sendUpdateLog?->id,
            'sageProcessRequestType' => SageEnum::SAGE_PROCESS_POST_PREPAYMENT_REQUEST,
        ];
        $requestData = [
            'quoteType' => $quoteType,
            'paymentSplit' => $paymentSplit->id,
            'quoteRequestId' => $quote->id,
            'sendUpdateId' => $sendUpdateLog?->id,
        ];
        $sageProcessData = [
            'user_id' => auth()->id(),
            'insurance_provider_id' => $insurer?->id,
            'request' => json_encode([
                'sagePayload' => $sagePayload,
                'requestPayload' => $requestData,
            ]),
            'status' => SageEnum::SAGE_PROCESS_PENDING_STATUS,
        ];

        $sageProcessWhereClause = [
            'model_type' => $paymentSplit::class,
            'model_id' => $paymentSplit->id,
        ];

        $sageProcess = SageProcess::where($sageProcessWhereClause)->first();

        if ($sageProcess) {
            if ($sageProcess->status == SageEnum::SAGE_PROCESS_FAILED_STATUS) {
                $sageProcess->update($sageProcessData);
            }
        } else {
            $sageProcess = SageProcess::create(array_merge($sageProcessData, $sageProcessWhereClause));
        }
        LoggerService::info('Sage process scheduled for prepayment posting for Payment split successfully.', extra : [
            'data' => json_encode(['sageProcessId' => $sageProcess->id,  'sageProcessStatus' => $sageProcess->status]),
            'PaymentSplitID' => $paymentSplit?->id,
            'PaymentSplitCode' => $paymentSplit?->code,
        ]);

        $this->scheduleSageProcesses($insurer?->id);
        LoggerService::info('scheduled sage processes triggered for Insurer: '.$insurer?->id);

        $response['status'] = true;
        $response['message'] = 'Prepayment is scheduled for Posting';

        return $response;
    }

    public function preChecksForPostPrepaymentSchedule($quote, $sendUpdateLog, $paymentSplit)
    {
        $response = ['status' => false, 'message' => null, 'errors' => []];
        if (! $this->isSageEnabled()) {
            LoggerService::info('Sage is not enabled');
            $response['errors']['sage'] = 'Sage is not enabled.';

            return $response;
        }

        if (! $paymentSplit) {
            $response['errors']['payment_split'] = 'Payment Split not found.';
        }

        if (! $this->shouldCreateAndSchedulePostPrepayment($quote, $paymentSplit)) {
            $key = $sendUpdateLog ? 'Send Update' : 'Quote';
            $response['errors']['booking-status'] = 'Post Prepayment cannot be done as '.$key.' is not booked yet.';
        }

        $prepaymentReceiptStatus = $paymentSplit->prepayment_receipt_status;
        LoggerService::info('Policy and prepayment receipt status', extra: [
            'data' => json_encode(['quote' => $quote?->quote_status_id, 'prepayment_receipt_status' => $paymentSplit->prepayment_receipt_status]),
        ]);
        if (! $prepaymentReceiptStatus['batchNumber']) {
            $response['errors']['prepayment'] = 'Prepayment for this Payment split is not generated on Sage.';
        }

        if ($prepaymentReceiptStatus['isPrepaymentAlreadyPosted']) {
            $response['errors']['prepayment_posted'] = 'Payment has already been posted to Sage.';
        }

        if (count($response['errors']) > 0) {
            return $response;
        }

        $response['status'] = true;

        return $response;

    }

    public function postPrepaymentToSage($data)
    {
        $response = ['status' => false, 'message' => null, 'error' => null];

        [$paymentSplit, $sageRequest, $request] = $data;

        $paymentSplit = $paymentSplit->refresh();

        try {

            $quote = $this->getQuoteObject($request->quoteType, $request->quoteRequestId);
            if ($request->sendUpdateId) {
                $quote = SendUpdateLog::whereId($request->sendUpdateId)->first();
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' - Starting postPrepaymentToSage for payment split ID: '.$paymentSplit->id.', Quote Code: '.$quote?->code);

            $arPrepaymentReceiptResponse = $this->executeSingleARPrepaymentReceiptPost([$sageRequest, $quote, $paymentSplit]);

            LoggerService::info(self::class.' fn: '.__FUNCTION__." - Completed arPrepaymentReceipt for payment split ID: {$paymentSplit->id}", extra: [
                'status' => $arPrepaymentReceiptResponse['status'],
                'message' => $arPrepaymentReceiptResponse['message'],
            ]);

            if (! $arPrepaymentReceiptResponse['status']) {
                $response['message'] = $arPrepaymentReceiptResponse['message'];
                $response['error'] = $arPrepaymentReceiptResponse['error'];

                return $response;
            }

            $apPrepaymentReceiptResponse = $this->executeSingleAPPrepaymentReceiptPost([$sageRequest, $quote, $paymentSplit]);

            LoggerService::info(self::class.' fn: '.__FUNCTION__." - Completed apPrepaymentReceipt for payment split ID: {$paymentSplit->id}", extra: [
                'status' => $apPrepaymentReceiptResponse['status'],
                'message' => $apPrepaymentReceiptResponse['message'],
            ]);

            if (! $apPrepaymentReceiptResponse['status']) {
                $response['message'] = $apPrepaymentReceiptResponse['message'];
                $response['error'] = $apPrepaymentReceiptResponse['error'];

                return $response;
            }

            $response['status'] = true;
            $response['message'] = 'AR and AP Prepayments are posted to Sage';

            return $response;
        } catch (\Exception $e) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__." - Error in postPrepaymentToSage for payment split ID: {$paymentSplit->id}", extra : [
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => false,
                'message' => 'Error posting AR and AP prepayments to Sage : '.$e->getMessage(),
            ];
        }
    }

    private function executeSingleARPrepaymentReceiptPost($sageRequestDataArray): array
    {
        [$sageRequest, $quote, $paymentSplit] = $sageRequestDataArray;
        $postedReceiptStatus = ['status' => false, 'message' => null, 'error' => null];

        $isPaymentMethodCreditApprove = $paymentSplit->payment_method == PaymentMethodsEnum::CreditApproval;
        if ($isPaymentMethodCreditApprove) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' Posting of prepayment skipped due to credit approval :  '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
            $postedReceiptStatus['status'] = true;
            $postedReceiptStatus['message'] = 'Posting of prepayment skipped due to credit approval : '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;

            return $postedReceiptStatus;
        }

        $sageLogArray = $paymentSplit->sageApiLogs->keyBy('step')->toArray();
        if (! isset($sageLogArray[2])) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API - AR Posting of Prepayment skipped as Creation of prepayment is not found - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' - code: '.$quote->code);
            $postedReceiptStatus['status'] = true;
            $postedReceiptStatus['message'] = 'AR Posting of Prepayment skipped as Creation of prepayment is not found : '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;

            return $postedReceiptStatus;
        }
        $sageResponse = json_decode($sageLogArray[2]['response'], true);

        if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep4 = true;
            $aRPostReceipts = SagePayloadFactory::aRPostReceiptsPayment($sageResponse['BatchNumber']);

            if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API :  AR Prepayment Receipt Posted Already - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                $isLiveApiCallStep4 = false;
                $postedResponse = json_decode($sageLogArray[4]['response'], true);

                $postedReceiptStatus['status'] = true;
                $postedReceiptStatus['message'] = 'AR Prepayment Receipt Posted Already - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;
            } else {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Checking status of AR Prepayment Receipt batch '.$sageResponse['BatchNumber'].' - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                $arPrePaymentReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                $arPrePaymentReceiptBatch = json_decode($arPrePaymentReceiptBatch, true);
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Status of AR Prepayment Receipt batch '.$sageResponse['BatchNumber'].' - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no, extra: $arPrePaymentReceiptBatch ?? []);

                if (! isset($arPrePaymentReceiptBatch['BatchStatus'])) {
                    $postedReceiptStatus['message'] = 'AR Prepayment batch status key not defined';
                    $postedReceiptStatus['error'] = 'AR Prepayment batch status key not defined';

                    return $postedReceiptStatus;
                }
                if ($arPrePaymentReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : AR Prepayment Receipt batch '.$sageResponse['BatchNumber'].' already posted - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                    $postedResponse = $aRPostReceipts['payload'];
                } else {
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Send AR Post Receipts Payment - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                    $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $isLiveApiCallStep4 = false;
                $errorMessage = 'Error while making AR Prepayment Receipt Posted to sage';
                $message = 'AR Post Receipts Payment - '.$sageResponse['BatchNumber'].' failed - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;
                $logResponse = $this->logErrorAndReturn([$paymentSplit, $message, $errorMessage, $aRPostReceipts, $postedResponse, 4, 4, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                $postedReceiptStatus['status'] = $logResponse['status'];
                $postedReceiptStatus['message'] = $logResponse['message'];
                $postedReceiptStatus['error'] = $logResponse['error'];
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : '.$quote->code.' : AR Post Receipts Payment - '.$sageResponse['BatchNumber'].' completed successfully - Child payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
            if ($isLiveApiCallStep4) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $paymentSplit, $quote, 4, 4, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                $postedReceiptStatus['status'] = true;
                $postedReceiptStatus['message'] = 'AR Prepayment Receipt posted on sage - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;
            }

            return $postedReceiptStatus;
        }

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API - Error found: AR Prepayment receipt is not ready to be post - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' - code: '.$quote->code);
        $postedReceiptStatus['status'] = false;
        $postedReceiptStatus['message'] = 'Error found: AR Prepayment receipt is not ready to be post - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;

        return $postedReceiptStatus;
    }

    private function executeSingleAPPrepaymentReceiptPost($sageRequestDataArray): array
    {
        [$sageRequest, $quote, $paymentSplit] = $sageRequestDataArray;
        $postedReceiptStatus = ['status' => false, 'message' => null, 'error' => null];

        $isPaymentMethodCreditApprove = $paymentSplit->payment_method == PaymentMethodsEnum::CreditApproval;
        if ($isPaymentMethodCreditApprove) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API :  Quote Code : '.$quote->code.' Posting of prepayment skipped due to credit approval :  '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
            $postedReceiptStatus['status'] = true;
            $postedReceiptStatus['message'] = 'Posting of prepayment skipped due to credit approval : '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;

            return $postedReceiptStatus;
        }

        $sageLogArray = $paymentSplit->sageApiLogs->keyBy('step')->toArray();
        if (! isset($sageLogArray[5])) {
            LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API - AP Posting of Prepayment skipped as Creation of prepayment is not found - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' - code: '.$quote->code);
            $postedReceiptStatus['status'] = true;
            $postedReceiptStatus['message'] = 'AP Posting of Prepayment skipped as Creation of prepayment is not found : '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;

            return $postedReceiptStatus;
        }
        $sageResponse = json_decode($sageLogArray[5]['response'], true);

        if (isset($sageLogArray[6]) && $sageLogArray[6]['status'] == SageEnum::STATUS_SUCCESS) {
            $isLiveApiCallStep7 = true;
            $aRPostReceipts = SagePayloadFactory::postAPPaymentReceiptPayload($sageResponse['BatchNumber']);

            if (isset($sageLogArray[7]) && $sageLogArray[7]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : AP Prepayment Receipt Posted Already - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                $isLiveApiCallStep7 = false;
                $postedResponse = json_decode($sageLogArray[7]['response'], true);

                $postedReceiptStatus['status'] = true;
                $postedReceiptStatus['message'] = 'AP Prepayment Receipt Posted Already - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;
            } else {
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Checking status of AP Prepayment Receipt batch '.$sageResponse['BatchNumber'].' - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                $arPrePaymentReceiptBatch = $this->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                $arPrePaymentReceiptBatch = json_decode($arPrePaymentReceiptBatch, true);
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Status of AP Prepayment Receipt batch '.$sageResponse['BatchNumber'].' - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no, extra: $arPrePaymentReceiptBatch ?? []);

                if (! isset($arPrePaymentReceiptBatch['BatchStatus'])) {
                    $postedReceiptStatus['message'] = 'AP Prepayment batch status key not defined';
                    $postedReceiptStatus['error'] = 'AP Prepayment batch status key not defined';

                    return $postedReceiptStatus;
                }
                if ($arPrePaymentReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : AP Prepayment Receipt batch '.$sageResponse['BatchNumber'].' already posted - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                    $postedResponse = $aRPostReceipts['payload'];
                } else {
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Send AP Post Receipts Payment - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
                    $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $isLiveApiCallStep7 = false;
                $errorMessage = 'Error while making AP Prepayment Receipt Posted to sage';
                $message = 'AP Post Receipts Payment - '.$sageResponse['BatchNumber'].' failed - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;
                $logResponse = $this->logErrorAndReturn([$paymentSplit, $message, $errorMessage, $aRPostReceipts, $postedResponse, 7, 7, SageEnum::STATUS_FAIL, $sageRequest->userId]);
                $postedReceiptStatus['status'] = $logResponse['status'];
                $postedReceiptStatus['message'] = $logResponse['message'];
                $postedReceiptStatus['error'] = $logResponse['error'];
            }

            LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : '.$quote->code.' : AP Post Receipts Payment - '.$sageResponse['BatchNumber'].' completed successfully - Child payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no);
            if ($isLiveApiCallStep7) {
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $paymentSplit, $quote, 7, 7, SageEnum::STATUS_SUCCESS, $sageRequest->userId);
                $postedReceiptStatus['status'] = true;
                $postedReceiptStatus['message'] = 'AP Prepayment Receipt posted on sage - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;
            }

            return $postedReceiptStatus;
        }

        LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API - Error found: AP Prepayment receipt is not ready to be post - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' - code: '.$quote->code);
        $postedReceiptStatus['status'] = false;
        $postedReceiptStatus['message'] = 'Error found: AP Prepayment receipt is not ready to be post - split payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no;

        return $postedReceiptStatus;
    }

    // TODO : Finance said they don't need it any more
    private function createARPrepaymentCommissionReceipts($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null, 'documentNumber' => null];
        $isAutomaticCommissionTransferEnabledForInsurer = (new BrokerCommissionService)->isAutomaticCommissionTransferEnabledForInsurer([
            $sageRequest->insurerID, $sageRequest->quoteTypeId, $sageRequest->subClass, $sageRequest->planId,
        ]);
        LoggerService::info(self::class.'fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Broker Commission -  AutomaticCommissionTransfer ', ['data' => json_encode([
            'AutomaticCommissionTransfer' => $isAutomaticCommissionTransferEnabledForInsurer,
        ]),
        ]);

        if (! $isAutomaticCommissionTransferEnabledForInsurer) {
            LoggerService::info(self::class.'fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Skip Creation of Commission prepayment as AutomaticCommissionTransfer is '.($isAutomaticCommissionTransferEnabledForInsurer ? ' enabled.' : ' disabled.'));

            $returnMessage['status'] = true;
            $returnMessage['message'] = 'Skip Creation of Commission prepayment as AutomaticCommissionTransfer is '.$isAutomaticCommissionTransferEnabledForInsurer;

            return $returnMessage;
        }

        LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Start Create Commission Prepayment Receipt for '.$quote->code);

        $totalSteps = 24;
        $stepsMapping = ['step_1' => 22, 'step_2' => 23, 'step_3' => 24];

        $isLiveApiCallStep22 = true;
        $payLoadOptions = SagePayloadFactory::createPrepaymentReceiptPayload($sageRequest, true);

        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Commission Prepayment Receipt Already created');
            $isLiveApiCallStep22 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' SAGE API : Send  Create Commission Prepayment Receipt for '.$quote->code);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {

            $commissionDocumentNumber = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            if ($commissionDocumentNumber) {
                $this->handleWithDeadlockRetries(function () use ($payment, $commissionDocumentNumber) {
                    $payment->update([
                        'sage_commission_receipt_id' => $commissionDocumentNumber,
                    ]);
                }, 5);
            }
            LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Updated Commission Prepayment Receipt Id ', ['data' => json_encode([
                'paymentCode' => $payment->code, 'sageCommissionReceiptId' => $commissionDocumentNumber,
            ]),
            ]);

            LoggerService::info(self::class.'fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' :  Commission Prepayment Receipt Batch Number - '.$sageResponse['BatchNumber']);
            if ($isLiveApiCallStep22) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS);
            }
            $isLiveApiCallStep23 = true;
            $readyToPostComPrepaymentReceipt = SagePayloadFactory::readyToPostReceiptArPayment($sageResponse['BatchNumber'], true);
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Commission Prepayment Receipt Already change to Ready To Post Status '.$quote->code);
                $isLiveApiCallStep23 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
            } else {
                LoggerService::info('SAGE API :  Send readyToPostComPrepaymentReceipt  for '.$quote->code);
                $readyToPostResponse = $this->postToSage300($readyToPostComPrepaymentReceipt['endPoint'], $readyToPostComPrepaymentReceipt['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making Ar commission prepayment receipt ready to post to sage';
                $message = 'Failed to Change status of Commission Receipt Batch - '.$sageResponse['BatchNumber'].' to Ready To Post.';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostComPrepaymentReceipt, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL]);
            }
            LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' : readyToPostComPrepaymentReceipt - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep23) {
                $this->logSageApiCall($readyToPostComPrepaymentReceipt, $readyToPostResponse, $quote, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS);
            }

            $isLiveApiCallStep24 = true;
            $postComPrepaymentReceipt = SagePayloadFactory::aRPostReceiptsPayment($sageResponse['BatchNumber'], true);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Post Commission Prepayment Receipt  Sent Already for '.$quote->code);
                $isLiveApiCallStep24 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {

                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' :  Checking status of AR Commission Prepayment Receipt batch '.$sageResponse['BatchNumber']);
                $arComPrePaymentReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$sageResponse['BatchNumber'].')', [], 'GET');
                $arComPrePaymentReceiptBatch = json_decode($arComPrePaymentReceiptBatch, true);
                LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API :  Status of AR Commission Prepayment Receipt batch '.$sageResponse['BatchNumber'].' - Batch Status: ', extra: $arComPrePaymentReceiptBatch);

                if (! isset($arComPrePaymentReceiptBatch['BatchStatus'])) {
                    $message = 'AR Commission Prepayment, Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }

                if ($arComPrePaymentReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info(self::class.' fn: '.__FUNCTION__.' SAGE API : AR Commission Prepayment Receipt batch '.$sageResponse['BatchNumber'].' already posted');
                    $postedResponse = $postComPrepaymentReceipt['payload'];
                } else {
                    LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' Post Commission Prepayment receipt  for '.$quote->code);
                    $resp = $this->postToSage300($postComPrepaymentReceipt['endPoint'], $postComPrepaymentReceipt['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making Ar invoice & prem Posted to sage';
                $message = 'Commission Prepayment receipt - '.$sageResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postComPrepaymentReceipt, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL]);
            }
            LoggerService::info('fn:'.__FUNCTION__.' SAGE API : Quote Code : '.$quote->code.' : Post Commission Prepayment receipt - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep24) {
                $this->logSageApiCall($postComPrepaymentReceipt, $postedResponse, $quote, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS);
            }
        } else {
            $errorMessage = 'Creation of Commission Prepayment Receipt Failed';
            $message = 'Creation of Commission Prepayment Receipt Failed  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL]);
        }
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR  Commission Prepayment Receipt created on sage';

        return $returnMessage;

    }

    public function applyPaymentAPInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $isTotalPriceZero = $payment->total_price == 0;

        /* applyPaymentAPInvoices */
        $isTransactionPaidAndFrequencyUpfront = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::UPFRONT;

        if ($isTransactionPaidAndFrequencyUpfront && ! $isTotalPriceZero) {
            return $this->applyUpfrontPaymentAPInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        } elseif ($isTotalPriceZero) {
            LoggerService::info('########## applyUpfrontPaymentAPInvoices skipped  for : '.$quote->code.' due to zero price ########## ');
        }

        $isFrequencySplitAndFirstChildPaymentPaid = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

        if ($isFrequencySplitAndFirstChildPaymentPaid) {
            return $this->applySplitPaymentAPInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        }

        $isFrequencyUpfrontOrSplit = in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]);
        $isFirstPaymentPaidOrCaptured = in_array($paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
        if (! $isFrequencyUpfrontOrSplit && $isFirstPaymentPaidOrCaptured) {
            return $this->applyNonSplitNonUpfrontPaymentAPInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        }

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Apply AP Prepayment completed';

        return $returnMessage;
    }

    private function applyUpfrontPaymentAPInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        LoggerService::info('########## Start applyPaymentAPInvoices for : '.$quote->code.' ##########');
        $totalSteps = 18;
        // 13
        $currentStep = 16;
        $isLiveApiCallStep16 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API : createUpfrontApplyPaymentAPInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep16 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('SAGE API :  Send createUpfrontApplyPaymentAPInvoice  for '.$quote->code);
            $payLoadOptions = SagePayloadFactory::createUpfrontApplyPaymentAPInvoicePayload($quote, $sageRequest->sageVenderId, $payment, $paymentSplits, true);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep16) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making split prepayments to sage';
            $message = 'createUpfrontApplyPaymentAPInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        LoggerService::info('SAGE API : '.$quote->code.' : createUpfrontApplyPaymentAPInvoice  - BatchNumber : '.$batchNumber.' completed successfully');
        // 14
        $currentStep = 17;
        $isLiveApiCallStep17 = true;
        $readyToPostReceiptAp = SagePayloadFactory::readyToPostUpfrontApplyPaymentAPInvoicePayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API :  readyToPostUpfrontApplyPaymentAPInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep17 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('SAGE API :  Send readyToPostUpfrontApplyPaymentAPInvoice  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAp['endPoint'], $readyToPostReceiptAp['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostUpfrontApplyPaymentAPInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAp, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            LoggerService::info('SAGE API : '.$quote->code.' : readyToPostUpfrontApplyPaymentAPInvoice completed successfully');
            if ($isLiveApiCallStep17) {
                $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $quote, $quote, $currentStep, $totalSteps);
            }
        }

        // delay is added because we are experiencing an error while posting AP Mapping
        sleep(3);

        // 15
        $currentStep = 18;
        $isLiveApiCallStep18 = true;
        $aPPostReceipts = SagePayloadFactory::postUpfrontApplyPaymentAPInvoicePayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API :  postUpfrontApplyPaymentAPInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep18 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('SAGE API :  Check status of  AP Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aPReceiptBatch = $this->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$batchNumber.')', [], 'GET');
                $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                LoggerService::info('SAGE API :  Status of  AP Prepayment Receipts batch('.$batchNumber.') : ', $aPReceiptBatch);
                if (! isset($aPReceiptBatch['BatchStatus'])) {
                    $message = 'Apply Upfront PrePayment, Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aPPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('SAGE API :  Send postUpfrontApplyPaymentAPInvoice  for '.$quote->code);
                $resp = $this->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        $sageErrorMessageOnSuccess = $postedResponse['Message'] ?? null;
        $isErrorOccurred = $sageErrorMessageOnSuccess && str_contains($sageErrorMessageOnSuccess, SageEnum::SAGE_ERROR_OCCURRED_MESSAGE);

        if (isset($postedResponse['error']) || $isErrorOccurred) {
            $errorMessage = $isErrorOccurred ? $sageErrorMessageOnSuccess : 'Error while making Apply payment Posted to sage';
            $message = $isErrorOccurred ? $sageErrorMessageOnSuccess : 'postUpfrontApplyPaymentAPInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        LoggerService::info('SAGE API : '.$quote->code.' : postUpfrontApplyPaymentAPInvoice completed successfully');
        if ($isLiveApiCallStep18) {
            $this->logSageApiCall($aPPostReceipts, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }
        LoggerService::info('########## End applyPaymentAPInvoices for : '.$quote->code.' ##########');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Prepayments applied on sage';

        return $returnMessage;
    }

    private function applySplitPaymentAPInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        LoggerService::info('########## Start createSplitApplyPaymentAPInvoice for : '.$quote->code.' ##########');
        $totalSteps = 18;

        // 12
        $currentStep = 16;
        $isLiveApiCallStep16 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API :  createSplitApplyPaymentAPInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep16 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('SAGE API :  Send createSplitApplyPaymentAPInvoice  for '.$quote->code);
            // For AP split payments, we'll use the same createSplitApplyPaymentAPInvoice method
            $readyToPostReceiptAp = SagePayloadFactory::createSplitApplyPaymentAPInvoicePayload($quote, $sageRequest->sageVenderId, $payment, $paymentSplits, true);
            $resp = $this->postToSage300($readyToPostReceiptAp['endPoint'], $readyToPostReceiptAp['payload'], 'POST');
            $response = json_decode($resp, true);
        }

        if (isset($response['error'])) {
            $errorMessage = 'Error while making Apply split prepayments to sage';
            $message = ' createSplitApplyPaymentAPInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAp, $response, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }

        if ($isLiveApiCallStep16) {
            $this->logSageApiCall($readyToPostReceiptAp, $response, $quote, $quote, $currentStep, $totalSteps);
        }

        $batchNumber = $response['BatchNumber'];
        LoggerService::info('SAGE API : '.$quote->code.' : readyToPostInvoiceAp - BatchNumber : '.$batchNumber.' completed successfully');
        // 14
        $currentStep = 17;
        $isLiveApiCallStep17 = true;
        $readyToPostReceiptAp = SagePayloadFactory::readyToPostSplitApplyPaymentAPInvoicePayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API :  readyToPostSplitApplyPaymentAPInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep17 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('SAGE API :  Send readyToPostSplitApplyPaymentAPInvoice  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAp['endPoint'], $readyToPostReceiptAp['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = ' readyToPostSplitApplyPaymentAPInvoice - BatchNumber : '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAp, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            LoggerService::info('SAGE API : '.$quote->code.' :  readyToPostSplitApplyPaymentAPInvoice - BatchNumber : '.$batchNumber.' completed successfully');
            if ($isLiveApiCallStep17) {
                $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $quote, $quote, $currentStep, $totalSteps);
            }
        }

        // delay is added because we are experiencing an error while posting AP Mapping
        sleep(3);
        // 15
        $currentStep = 18;
        $isLiveApiCallStep18 = true;
        $aPPostReceipts = SagePayloadFactory::postSplitApplyPaymentAPInvoicePayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API : postSplitApplyPaymentAPInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep18 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {

            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('SAGE API :  Check status of  AP Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aPReceiptBatch = $this->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$batchNumber.')', [], 'GET');
                $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                LoggerService::info('SAGE API :  Status of  AP Prepayment Receipts batch('.$batchNumber.') : ', $aPReceiptBatch);
                if (! isset($aPReceiptBatch['BatchStatus'])) {
                    $message = 'Apply Split PrePayment, Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aPPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('SAGE API : Send postSplitApplyPaymentAPInvoice  for '.$quote->code);
                $resp = $this->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        $sageErrorMessageOnSuccess = $postedResponse['Message'] ?? null;
        $isErrorOccurred = $sageErrorMessageOnSuccess && str_contains($sageErrorMessageOnSuccess, SageEnum::SAGE_ERROR_OCCURRED_MESSAGE);

        if (isset($postedResponse['error']) || $isErrorOccurred) {
            $errorMessage = $isErrorOccurred ? $sageErrorMessageOnSuccess : 'Error while making Apply payment Posted to sage';
            $message = $isErrorOccurred ? $sageErrorMessageOnSuccess : ' postSplitApplyPaymentAPInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        LoggerService::info('SAGE API : '.$quote->code.' : postSplitApplyPaymentAPInvoice completed successfully');
        if ($isLiveApiCallStep18) {
            $this->logSageApiCall($aPPostReceipts, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }
        LoggerService::info('########## End postSplitApplyPaymentAPInvoice for : '.$quote->code.' ##########');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Prepayments applied on sage';

        return $returnMessage;
    }

    private function applyNonSplitNonUpfrontPaymentAPInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        LoggerService::info('########## Start applyNonSplitNonUpfrontPaymentAPInvoices for : '.$quote->code.' ##########');
        $totalSteps = 21;

        // 16
        $currentStep = 19;
        $isLiveApiCallStep19 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API : apSplitPrepaymentPayload  Sent Already for '.$quote->code);
            $isLiveApiCallStep19 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('SAGE API : Send apSplitPrepaymentPayload  for '.$quote->code);
            $readyToPostReceiptAp = SagePayloadFactory::createSplitApplyPaymentAPInvoicePayload($quote, $sageRequest->sageVenderId, $payment, $paymentSplits, false);
            $resp = $this->postToSage300($readyToPostReceiptAp['endPoint'], $readyToPostReceiptAp['payload'], 'POST');
            $response = json_decode($resp, true);
        }

        if (isset($response['error'])) {
            $errorMessage = 'Error while making Apply split prepayments to sage';
            $message = 'apSplitPrepaymentPayload failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAp, $response, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        if ($isLiveApiCallStep19) {
            $this->logSageApiCall($readyToPostReceiptAp, $response, $quote, $quote, $currentStep, $totalSteps);

        }
        $batchNumber = $response['BatchNumber'];
        LoggerService::info('SAGE API : '.$quote->code.' : readyToPostInvoiceAp - BatchNumber : '.$batchNumber.' completed successfully');
        // 16
        $currentStep = 20;
        $isLiveApiCallStep20 = true;
        $readyToPostReceiptAp = SagePayloadFactory::readyToPostSplitApplyPaymentAPInvoicePayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API :  readyToPostReceiptApPayment  Sent Already for '.$quote->code);
            $isLiveApiCallStep20 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            LoggerService::info('SAGE API :  Send readyToPostReceiptApPayment  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAp['endPoint'], $readyToPostReceiptAp['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptApPayment  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAp, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            LoggerService::info('SAGE API : '.$quote->code.' : readyToPostReceiptApPayment completed successfully');
            if ($isLiveApiCallStep20) {
                $this->logSageApiCall($readyToPostReceiptAp, $readyToPostResponse, $quote, $quote, $currentStep, $totalSteps);
            }
        }

        // delay is added because we are experiencing an error while posting AP Mapping
        sleep(3);

        $currentStep = 21;
        $isLiveApiCallStep21 = true;
        $aPPostReceipts = SagePayloadFactory::postSplitApplyPaymentAPInvoicePayload($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            LoggerService::info('SAGE API :  aPPostReceiptsPayment  Sent Already for '.$quote->code);
            $isLiveApiCallStep21 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                LoggerService::info('SAGE API :  Check status of  AP Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aPReceiptBatch = $this->postToSage300("AP/APPaymentAndAdjustmentBatches(BatchSelector='PY',BatchNumber=".$batchNumber.')', [], 'GET');
                $aPReceiptBatch = json_decode($aPReceiptBatch, true);

                LoggerService::info('SAGE API :  Status of  AP Prepayment Receipts batch('.$batchNumber.') ', $aPReceiptBatch);
                if (! isset($aPReceiptBatch['BatchStatus'])) {
                    $message = 'Apply Non Split Non Upfront Payment , Unable to get Batch Status from Sage.';
                    $returnMessage['message'] = $message;
                    $returnMessage['error'] = $message;

                    return $returnMessage;
                }
                if ($aPReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    LoggerService::info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aPPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                LoggerService::info('SAGE API :  Send aPPostReceiptsPayment  for '.$quote->code);
                $resp = $this->postToSage300($aPPostReceipts['endPoint'], $aPPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }

        }

        $sageErrorMessageOnSuccess = $postedResponse['Message'] ?? null;
        $isErrorOccurred = $sageErrorMessageOnSuccess && str_contains($sageErrorMessageOnSuccess, SageEnum::SAGE_ERROR_OCCURRED_MESSAGE);

        if (isset($postedResponse['error']) || $isErrorOccurred) {
            $errorMessage = $isErrorOccurred ? $sageErrorMessageOnSuccess : 'Error while making Apply payment Posted to sage';
            $message = $isErrorOccurred ? $sageErrorMessageOnSuccess : 'aPPostReceiptsPayment - BatchNumber '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        if ($isLiveApiCallStep21) {
            // TODO:: $postedResponse need to defined properly
            $this->logSageApiCall($aPPostReceipts, $postedResponse, $quote, $quote, $currentStep, $totalSteps);
        }
        LoggerService::info('SAGE API : '.$quote->code.' : aPPostReceiptsPayment - BatchNumber '.$batchNumber.' completed successfully');
        LoggerService::info('  ########## End apSplitPrepaymentPayload for : '.$quote->code.' ########## ');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AP Prepayments applied on sage';

        return $returnMessage;
    }

    public function getSageCustomerNumber($quoteDetails, $customerId, $customerData, $paymentSplit, $advisorId)
    {
        $response = ['status' => false, 'message' => '', 'error' => '', 'sageCustomerNumber' => null];
        $sageApiService = new SageApiService;
        $sageCustomerNumber = $sageApiService->verifySageCustomer($customerId, $customerData, $paymentSplit, 4, $advisorId);
        if (empty($sageCustomerNumber)) {
            LoggerService::info('Customer not found in Sage', extra : ['PaymentCode' => $paymentSplit->code, 'SerialNumber' => $paymentSplit->sr_no]);
            $response['message'] = 'Customer not found in sage - Ref:'.$quoteDetails->code;

            return $response;
        }
        LoggerService::info('Sage customer number verified', extra : ['SageCustomerNumber' => $sageCustomerNumber]);

        $response['status'] = true;
        $response['message'] = 'Sage customer number verified';
        $response['sageCustomerNumber'] = $sageCustomerNumber;

        return $response;
    }

    public function isLobAllowedForEmbeddedProductBooking($quoteTypeId)
    {
        $lobAllowedForEmbeddedProductBooking = [QuoteTypeId::Car, QuoteTypeId::Bike];

        return in_array($quoteTypeId, $lobAllowedForEmbeddedProductBooking);
    }

    public function getEPTransactions($quote, $quoteTypeId)
    {
        $isAllowedLod = $this->isLobAllowedForEmbeddedProductBooking($quoteTypeId);
        if (! $isAllowedLod) {
            return null;
        }

        $allowedProvidersForSageEPBooking = $this->allowedProviderForSageEPBooking();

        return $quote->embeddedTransactions()
            ->whereHas('product.embeddedProduct', function ($query) {
                $query->whereIn('short_code', [EmbeddedProductEnum::MDX, EmbeddedProductEnum::RDX, EmbeddedProductEnum::ECB]);
            })
            ->whereHas('payment.insuranceProvider', function ($query) use ($allowedProvidersForSageEPBooking) {
                $query->whereIn('code', $allowedProvidersForSageEPBooking);
            })
            ->where('is_selected', 1)
            ->whereIn('payment_status_id', [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])
            ->get();
    }

    public function isEmbeddedTransactionStatusReadyForSage($quote, $quoteTypeId)
    {
        $ePTransactions = $this->getEPTransactions($quote, $quoteTypeId);
        if ($ePTransactions) {
            $totalEmbeddedTransactionCount = $ePTransactions->count();
            $totalReadyForSageEmbeddedTransactionCount = $ePTransactions->where('policy_status', EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE)->count();

            return $totalEmbeddedTransactionCount == $totalReadyForSageEmbeddedTransactionCount;
        }

        return true;
    }

    public function allowedProviderForSageEPBooking()
    {
        return [InsuranceProviderEnum::OIC->value, InsuranceProviderEnum::NGI->value];
    }

}
