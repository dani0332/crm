<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypeId;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Jobs\BookPolicyOnSageJob;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Jobs\SendUpdateSageJob;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatusLog;
use App\Models\QuoteTag;
use App\Models\SageApiLog;
use App\Models\SageProcess;
use App\Repositories\PaymentRepository;
use App\Repositories\SageApiLogRepository;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
use App\Traits\TeamHierarchyTrait;
use Cache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SageApiService
{
    use GenericQueriesAllLobs;
    use SageLoggable, TeamHierarchyTrait;

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
        info('Sage Customer verification - Process start - Quote Type: '.$data['quoteTypeId'].' - Ref ID: '.$data['id']);
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
            info('Sage Customer verification - customer already created on Sage - sage customer code:'.$customerNumber);
            $sageCustomerNumber = $customerResponse['CustomerNumber'];
            $this->logSageApiCall([
                'endPoint' => SageEnum::END_POINT_AR_CUSTOMER,
                'payload' => $sageCustomerFromDB ? [] : $payLoadOptions,
            ], '', $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $authUserId);
        }

        $responseError = isset($customerResponse['error']['code']) ? $customerResponse['error']['code'] : false;
        if ($responseError && $responseError == SageEnum::ERROR_RECORD_NOT_FOUND) {
            info('Sage Customer verification - customer not found in Sage - Calling Sage customer creation API');
            $customerResponse = json_decode($this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']), true);

            if ($customerResponse && isset($customerResponse['CustomerNumber'])) {
                info('Sage Customer verification - customer successfully created on Sage - customer code: '.$customerResponse['CustomerNumber']);
                $sageCustomerNumber = $customerResponse['CustomerNumber'];
                $this->logSageApiCall($payLoadOptions, $customerResponse, $logModal, 1, $totalSteps, SageEnum::STATUS_SUCCESS, $authUserId);
            } else {
                info('Sage Customer verification - Error while creating customer on Sage - Payload: '.json_encode($payLoadOptions['payload']).' - Response: '.(json_encode($customerResponse)));
                $this->logSageApiCall($payLoadOptions, $customerResponse, $logModal, 1, $totalSteps, SageEnum::STATUS_FAIL, $authUserId);
            }
        }

        if (! $sageCustomerFromDB && ($customerResponse && isset($customerResponse['CustomerNumber']))) {
            if ($quoteEntity) {
                $quoteEntity->sage_customer_number = $sageCustomerNumber;
                $quoteEntity->save();
                info('Sage Customer verification - sage customer code ('.$customerResponse['CustomerNumber'].') updated in entities table');
            } elseif ($customer) {
                $customer->sage_customer_number = $sageCustomerNumber;
                $customer->save();
                info('Sage Customer verification - sage customer code: ('.$customerResponse['CustomerNumber'].') updated in customers table');
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
                $responseBody = is_array($response->json()) ? $response->json() : json_decode($response->body());
                info('Sage API : '.$endPoint.' : '.$responseBody['error']['message']['value'] ?? 'Something went wrong with Sage Server.');
            }

            return is_array($response->json()) ? json_encode($response->json()) : $response->body();
        } catch (Exception $e) {
            logger()->error('Sage API : '.$endPoint.' : '.$e->getMessage());

            return json_encode(['error' => ['message' => ['value' => $e->getMessage()]], 'code' => 500]);
        }
    }

    public function sendUpdateSageLogs($sendUpdateRequest, $sendUpdateLog): array
    {
        $sageLogsArray = $reversalInvoiceLogs = [];
        $sendUpdateCategory = $sendUpdateLog?->category?->code;
        if (in_array($sendUpdateCategory, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            info('fn:sendUpdateSageLogs - Fetching logs for non CPD Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
            $sageLogsArray = $sendUpdateLog->sageApiLogs?->whereNotIn('entry_type', [
                SageEnum::SRT_GET_AR_INVOICE,
                SageEnum::SRT_GET_AP_INVOICE,
                SageEnum::SCT_REVERSAL,
                SageEnum::SCT_CORRECTION,
            ])->keyBy('step')->toArray();
        }

        if ($sendUpdateCategory == SendUpdateLogStatusEnum::CPD) {
            info('fn:sendUpdateSageLogs - Fetching logs for CPD Endorsement - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);

            $quoteModelObject = $this->getModelObject($sendUpdateRequest->quoteType);
            $quoteDetails = $quoteModelObject::where('id', $sendUpdateRequest->quoteRefId)->first();
            $getPaymentByInsurerInvoiceNumber = PaymentRepository::getPaymentByInsurerInvoiceNumber($quoteDetails, $sendUpdateRequest->reversalInvoice);
            if ($getPaymentByInsurerInvoiceNumber->send_update_log_id !== null) {
                // This case if the Reversal Invoice is Endorsement itself
                $getReverseInvoiceRelation = [
                    'section_type' => $sendUpdateLog->getMorphClass(),
                    'section_id' => $getPaymentByInsurerInvoiceNumber->send_update_log_id,
                ];
            } else {
                // This case if the Reversal Invoice is Main Lead
                $getReverseInvoiceRelation = [
                    'section_type' => $getPaymentByInsurerInvoiceNumber->paymentable_type,
                    'section_id' => $getPaymentByInsurerInvoiceNumber->paymentable_id,
                ];
            }

            $getReverseInvoicesLogs = SageApiLog::where($getReverseInvoiceRelation)
                ->whereNotIn('entry_type', [
                    SageEnum::SRT_GET_AR_INVOICE,
                    SageEnum::SRT_GET_AP_INVOICE,
                    SageEnum::SCT_REVERSAL,
                ])->orderBy('step')->get()->toArray();

            info('fn:sendUpdateSageLogs - Fetching reversal invoice logs for reverse and correction - QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
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
                info('fn:sendUpdateSageLogs - Reversal invoice logs not found for reverse and correction- QuoteType: '.$sendUpdateRequest->quoteType.' - QuoteUUID: '.$sendUpdateRequest->quoteUuid.' - SendUpdateCode: '.$sendUpdateLog->code);
            }

            // The Sage logs array for CPD should include GET logs for AR, AP, and Reversal/Correction invoices. Additionally, for discount adjustments, "Straight" should be included.
            // Reminder:: After including "Straight" for the discount, all entry types will be included in the logs array.
            $sageLogsArray = $sendUpdateLog->sageApiLogs?->keyBy('step')->toArray();
        }

        return [$sageLogsArray, $reversalInvoiceLogs];
    }

    public function bookEndorsementOnSage($endorsementPreparedPayload)
    {
        [$request, $sendUpdateLog, $sageRequestPayload] = $endorsementPreparedPayload;

        $response = ['status' => true, 'message' => 'Endorsement successfully booked'];
        $sendUpdateCategory = $sendUpdateLog?->category?->code;
        [$sageLogsArray, $reversalInvoiceLogs] = $this->sendUpdateSageLogs($request, $sendUpdateLog);

        $quoteModelObject = $this->getModelObject($request->quoteType);
        $mainQuote = $quoteModelObject::where('id', $request->quoteRefId)->first();
        $preparedData = (new SendUpdateLogService)->preparedDetailsForEndorsement($request, $mainQuote, $sendUpdateLog);

        $preparedData['quoteDetails'] = $quoteModelObject::where('id', $request->quoteRefId)->first();
        $preparedData['sendUpdateLog'] = $sendUpdateLog;

        if ($sendUpdateCategory == SendUpdateLogStatusEnum::CPD) {
            if (empty($reversalInvoiceLogs)) {
                return ['status' => false, 'message' => 'Reversal invoice logs not found for reverse and correction'];
            }

            $response = $this->bookReversalEndorsementOnSage($request, $preparedData, $sageRequestPayload, $sageLogsArray, $reversalInvoiceLogs, $sendUpdateLog);
        } else {
            $response = $this->bookStraightEndorsementOnSage($preparedData, $sageRequestPayload, $sageLogsArray);
        }

        if (! $response['status']) {
            return $response;
        }

        $response = app(SendUpdateLogService::class)->updatesMoveToLead([$request, $sendUpdateLog, $preparedData]);
        if (! $response['status']) {
            return $response;
        }

        return $response;
    }

    public function bookStraightEndorsementOnSage($preparedData, $sageRequestPayload, $sageLogsArray): array
    {
        info('fn bookStraightEndorsementOnSage - Upfront Endorsement Booking Start on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);
        info('fn bookStraightEndorsementOnSage - Payment frequency : '.$preparedData['payment']->frequency.' - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

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

        info('fn bookStraightEndorsementOnSage - Upfront Endorsement Booking Completed on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

        return ['status' => true, 'message' => 'Straight Forward Endorsement Booking Completed on Sage'];
    }

    public function bookReversalEndorsementOnSage($request, $preparedData, $sageRequestPayload, $sageLogsArray, $reversalInvoiceLogs, $sendUpdateLog): array
    {
        info('fn bookReversalEndorsementOnSage - Reversal and Correction Endorsement Booking Start on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);
        info('fn bookReversalEndorsementOnSage - Payment frequency : '.$preparedData['payment']->frequency);

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
                info('fn bookReversalEndorsementOnSage - Invalid Invoice Type - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

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

        info('fn bookReversalEndorsementOnSage - Reversal and Correction Endorsement Booking Completed on Sage - SendUpdateCode: '.$preparedData['sendUpdateLog']?->code);

        return ['status' => true, 'message' => 'Non Up-front Endorsement Booking Completed on Sage'];
    }

    private function getInvoiceDetailsForReversal($reversalInvoiceDetails): array
    {
        [$reversalInvoiceDetails, $sendUpdateLog, $getRequestType, $sageLogArray, $extraDetails] = $reversalInvoiceDetails;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $userId = $extraDetails['userId'] ?? null;

        info('fn getInvoiceDetailsForReversal - Getting Invoice from Sage process start - SendUpdateCode: '.$sendUpdateLog?->code);
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
            info('fn getInvoiceDetailsForReversal - Invalid Invoice Type - SendUpdateCode: '.$sendUpdateLog?->code);

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
                info('SAGE API : Reversal invoice number updated - previous reversal batch number ('.$sageLogArray[$step]['sage_end_point'].') - current reversal batch number ('.$payLoadOptions['endPoint'].') - Send Update Code: '.$sendUpdateLog->code);
                $getNewResponse = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'] ?? [], 'GET');
                $sageLogArray[$step]['response'] = $getNewResponse;
                $sageLogArray[$step]['sage_end_point'] = $payLoadOptions['endPoint'];
                SageApiLog::where('id', $sageLogArray[$step]['id'])->update([
                    'sage_end_point' => $payLoadOptions['endPoint'],
                    'response' => $getNewResponse,
                ]);
                info('SAGE API : Response against current batch number has been updated - Send Update Code: '.$sendUpdateLog->code);
            }

            info('SAGE API :  getInvoiceDetails for '.$reversalInvoiceDetails['invoiceType'].' Sent Already for '.$sendUpdateLog->code);
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[$step]['response'], true);
        } else {
            info('SAGE API :  Send getInvoiceDetails for '.$sendUpdateLog->code);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload'] ?? [], 'GET');
            $sageResponse = json_decode($resp, true);
        }

        if (empty($sageResponse)) {
            $errorMessage = 'Error while getting invoice from Sage for Reversal';
            $message = 'getInvoiceDetails failed';

            return $this->logErrorAndReturn([$sendUpdateLog, $message, $errorMessage, $payLoadOptions, $sageResponse, $step, 23, SageEnum::STATUS_FAIL, $userId]);
        } else {
            info('SAGE API : '.$sendUpdateLog->code.' : getInvoiceDetails completed successfully. Reversal Invoice Batch number: '.$sageResponse['BatchNumber'] ?? '');
            if ($isLiveApiCallStep2) {
                $payLoadOptions['entry_type'] = $sageEntryType;
                $payLoadOptions['sage_request_type'] = $sageRequestType;

                $this->logSageApiCall($payLoadOptions, $sageResponse, $sendUpdateLog, $step, 23, SageEnum::STATUS_SUCCESS, $userId);
            }
        }

        $returnMessage['response'] = $sageResponse;
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Invoice Details fetched successfully';

        info('fn getInvoiceDetailsForReversal - Getting Invoice from Sage process end - SendUpdateCode: '.$sendUpdateLog?->code);

        return $returnMessage;
    }

    public function postBookPolicyToSage($request, $quote)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        // check sage is enabled or not
        if (! $this->isSageEnabled()) {
            info('Policy Book : postBookPolicyToSage : Sage is not enabled');
            $returnMessage['message'] = 'Sage is not enabled';

            return $returnMessage;
        }
        $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($request->model_type));

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

        // Booking of Policies with zero price is only allowed for the policies having Credit Approval as Payment Method.
        $isPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
        $isPaymentMethodCreditApproved = $payment->payment_methods_code == PaymentMethodsEnum::CreditApproval;
        $isTotalPriceZero = $payment->total_price == 0;
        if (! $isPaymentMethodCreditApproved && $isTotalPriceZero && $isPaymentFrequencyUpfront) {
            info('Policy Book : postBookPolicyToSage : Please check the payment as total price is set to zero while Payment Method is '.PaymentMethodsEnum::CreditApproval.' and Frequency is '.$payment->frequency.'. Please Select Credit Approval as your payment method and Upfront as Payment Frequency to Proceed!');

            return ['status' => false, 'message' => 'Please check the payment as total price is set to zero while Payment Method is '.PaymentMethodsEnum::CreditApproval.' and Frequency is '.$payment->frequency.'. Please Select Credit Approval as your payment method and Upfront as Payment Frequency to Proceed!'];
        }

        // payload
        $sageRequest = app(SagePayloadFactory::class)->sagePayLoad($request->model_type, $payment, $quote, $paymentSplits);
        $sageRequest->quoteCode = $quote?->code;
        $sageRequest->quoteTypeId = $quoteTypeId;
        $sageRequest->sageProcessRequestType = SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST;

        // sage customer number generation
        $sageRequest->customerId = $this->verifySageCustomer($quote->customer_id, $data, $quote, 13);

        /* Check Sage Vendor ID, GL Account ID, Insurer Customer ID, and Sage Customer ID */
        $checkRequiredSageIds = $this->checkRequiredSageIds($sageRequest);
        if (! $checkRequiredSageIds['status']) {
            info('Policy Book : postBookPolicyToSage : '.$checkRequiredSageIds['message']);

            return $checkRequiredSageIds;
        }

        if ($quote->quote_status_id == QuoteStatusEnum::PolicyBooked) {
            info('Policy Book : postBookPolicyToSage : Policy has been already booked!');

            return ['status' => true, 'message' => 'Policy has been already booked!'];
        }

        $this->createSageProcess($quote, $sageRequest, $request);

        $this->updateAndLogQuoteStatus($quote, $sageRequest->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED, $sageRequest->userId);

        $this->scheduleSageProcesses($sageRequest->insurerID);
        info('Policy Book : postBookPolicyToSage : scheduleSageProcesses triggered for Insurer - '.$sageRequest->insurerID);

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
            info('Policy Book : postBookPolicyToSage : '.$message);

            return ['status' => false, 'message' => $message];
        }

        return ['status' => true, 'message' => ''];
    }

    public function bookPolicyOnSage($sageRequestDataArray)
    {
        [$sageRequest, $quote,  $request] = $sageRequestDataArray;
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

        info('################################## Sage Book Policy started for : '.$quote->code.' ##################################');

        if (! $isPolicyBookedOnSage) {

            info('Sage API : Payment frequency : '.$payment->frequency.' for '.$quote->code);

            // Create AR Commission and Premium Invoice
            $createARInvoicePremAndComm = $this->createARInvoicePremAndComm([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
            if (! $createARInvoicePremAndComm['status']) {
                return $createARInvoicePremAndComm;
            }

            // Create AP Premium Invoice
            $createAPInvoicePrem = $this->createAPInvoicePrem([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
            if (! $createAPInvoicePrem['status']) {
                return $createAPInvoicePrem;
            }

            // Create AR Discount Invoice
            $createARInvoiceDis = $this->createARInvoiceDis([$sageRequest, $quote, $sageLogArray]);
            if (! $createARInvoiceDis['status']) {
                return $createARInvoiceDis;
            }

            // Apply Prepayments
            $applyPaymentInvoices = $this->applyPaymentInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
            if (! $applyPaymentInvoices['status']) {
                return $applyPaymentInvoices;
            }

            QuoteTag::create([
                'quote_type_id' => $quoteTypeId,
                'quote_uuid' => $quote->uuid,
                'name' => QuoteTagEnums::POLICY_BOOKED_ON_SAGE,
                'value' => 1,
            ]);

            info('################################## Sage Policy Booked for : '.$quote->code.' ##################################');
        } else {
            info('################################## Sage Policy Booked Already for : '.$quote->code.' ##################################');
        }
        $skipBookPolicyDocumentJob = false;
        if ($quoteTypeId === QuoteTypeId::Travel) {
            $quote->load('policyIssuance');
            if ($quote->policyIssuance?->status == PolicyIssuanceEnum::COMPLETED_STATUS && ! $quote->advisor_id) {
                $skipBookPolicyDocumentJob = true;
            }
        }

        info('Quote code : '.$quote->code.' Skip book policy document job '.$skipBookPolicyDocumentJob ? 'Yes' : 'No');
        if (! $skipBookPolicyDocumentJob && ! (app(QuoteStatusService::class)->isPolicySentLogExists($quote->id))) {
            info('################################## Send Customer Documents to customer after booking of : '.$quote->code.' ##################################');
            // dispath job to send email
            SendBookPolicyDocumentsJob::dispatch($request, $quote->code);
        }

        info('################################## Policy Book : mark status as policy booked for : '.$quote->code.' ##################################');

        $this->updateAndLogQuoteStatus($quote, $quoteTypeId, QuoteStatusEnum::PolicyBooked, $userId);

        info('################################## Policy Book : Status updated to  : '.$quote->quote_status_id.' for '.$quote->code.' ##################################');

        info('################################## Policy Book : straightforwardPayments for : '.$quote->code.' ##################################');
        (new CentralService)->straightforwardPayments($payment, $paymentSplits, $quote);
        info('################################## Policy Book : straightforwardPayments for : '.$quote->code.' done ##################################');

        info('################################## Policy Book : updatePaymentAllocationStatus for : '.$quote->code.' ##################################');
        $this->updatePaymentAllocationStatus($quote);
        info('################################## Policy Book : updatePaymentAllocationStatus for : '.$quote->code.' done ##################################');

        info('########## End of Policy Booked for : '.$quote->code.' ##########');

        return ['status' => true, 'message' => 'Policy is Booked'];
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
            info('SAGE API :  createARInvoicePremAndComm  Sent Already for '.$quote->code);
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            info('SAGE API :  Send createARInvoicePremAndComm  for '.$quote->code);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($resp, true);
        }

        if (! empty($sageResponse['BatchNumber'])) {
            info('SAGE API : '.$quote->code.' :  Batch Number - '.$sageResponse['BatchNumber'].' for createARInvoicePremAndComm');
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
            $isLiveApiCallStep3 = true;
            $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr(batchNumber: $sageResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                info('SAGE API :  readyToPostInvoiceAr  Sent Already for '.$quote->code);
                $isLiveApiCallStep3 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
            } else {
                info('SAGE API :  Send readyToPostInvoiceAr  for '.$quote->code);
                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making Ar invoice & prem ready to post to sage';
                $message = 'readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
            info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep3) {
                $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }

            $isLiveApiCallStep4 = true;
            $aRPostInvoices = SagePayloadFactory::aRPostInvoices(batchNumber: $sageResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                info('SAGE API :  aRPostInvoices  Sent Already for '.$quote->code);
                $isLiveApiCallStep4 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                    info('SAGE API :  Check status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$quote->code);
                    $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$sageResponse['BatchNumber'].')', [], 'GET');
                    $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                    info('SAGE API :  Status of  AR invoice batch '.$sageResponse['BatchNumber'].'  for '.$quote->code.' is '.$arInvoiceBatch['BatchStatus']);
                    if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        info('SAGE API : AR invoice batch '.$sageResponse['BatchNumber'].' already posted for '.$quote->code);
                        $postedResponse = $aRPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    info('SAGE API :  Send aRPostInvoices  for '.$quote->code);
                    $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making Ar invoice & prem Posted to sage';
                $message = 'aRPostInvoices - '.$sageResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
            info('SAGE API : '.$quote->code.' : aRPostInvoices - '.$sageResponse['BatchNumber'].' completed successfully');
            if ($isLiveApiCallStep4) {
                //                TODO:: This need to be improve, posted response undefined if status not posted
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        } else {
            $errorMessage = 'Ar invoice & prem failed from sage';
            $message = 'createARInvoicePremAndComm  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $sageResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }
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

        if ($sageEntryType == SageEnum::SCT_CORRECTION) {
            $totalSteps = 24;
            $stepsMapping = ['step_1' => 6, 'step_2' => 7, 'step_3' => 8, 'step_4' => 9];
        }

        $isLiveApiCallStep2 = true;
        $createARInvoiceSplitPayments = SagePayloadFactory::createARInvoiceSplitPayments($sageRequest, $paymentSplits, $sageEntryType, $reverseInvoiceDetails, $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  createARInvoiceSplitPayments Sent Already for '.$quote->code);
            $isLiveApiCallStep2 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            info('SAGE API :  Send createARInvoiceSplitPayments for '.$quote->code);
            $resp = $this->postToSage300($createARInvoiceSplitPayments['endPoint'], $createARInvoiceSplitPayments['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (empty($postedResponse['BatchNumber'])) {
            $errorMessage = 'ar split payment failed from sage';
            $message = 'createARInvoiceSplitPayments  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createARInvoiceSplitPayments, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }
        info('SAGE API : '.$quote->code.' : createARInvoiceSplitPayments - BatchNumber : '.$postedResponse['BatchNumber'].' completed successfully');
        $batchNumber = $postedResponse['BatchNumber'];
        if ($isLiveApiCallStep2) {
            $this->logSageApiCall($createARInvoiceSplitPayments, $postedResponse, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
        }

        if ($sageEntryType !== SageEnum::SCT_REVERSAL) {
            $url = 'AR/ARInvoiceBatches('.$batchNumber.')';
            info('SAGE API :  Send Post AR/ARInvoiceBatches  for '.$quote->code);
            $resp = $this->postToSage300($url, [], 'GET');
            $postedResponse = json_decode($resp, true);

            if (empty($postedResponse['Invoices'][0]['InvoicePaymentSchedules'])) {
                $errorMessage = 'Error while getting ar2 split payments from sage';
                $message = 'get AR/ARInvoiceBatches for batchNumber : '.$batchNumber.' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, [], $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId], false);
            }
            info('SAGE API :  Prepare Patch payload for SpitPayments  for '.$quote->code);
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

            info('SAGE API :  Prepare Patch payload for Commission Splits  for '.$quote->code);

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
                info('SAGE API :  Patch Request Sent Already for '.$quote->code);
                $isLiveApiCallStep3 = false;
                $postedResponse = ! empty($sageLogArray[$stepsMapping['step_2']]['response']) ? json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true) : [];
            } else {
                info('SAGE API :  Send Patch Request  for '.$quote->code);
                $resp = $this->postToSage300($url, $postedResponse, 'PATCH');
                $postedResponse = json_decode($resp, true);
            }
            $postedResponse['endPoint'] = $url;
            $postedResponse['payload'] = $patchPayload;

            if (isset($postedResponse['error'])) {
                info('SAGE API : '.$quote->code.' : AR Patch Request failed '.json_encode($postedResponse['error']));
                $postedResponse['sage_request_type'] = SageEnum::SRT_AR_SPPAY_PAY_SCDULE_PATCH;
                $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;
                $errorMessage = 'Error while making ar2 split payments patch to sage';
                $message = 'AR Patch Request failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postedResponse, $postedResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
            if ($isLiveApiCallStep3) {
                $postedResponse['sage_request_type'] = SageEnum::SRT_AR_SPPAY_PAY_SCDULE_PATCH;
                $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;

                $this->logSageApiCall($postedResponse, $postedResponse, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        }

        // 4
        $isLiveApiCallStep4 = true;
        $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr(batchNumber: $batchNumber, type: $sageEntryType, extras: $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  readyToPostInvoiceAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep4 = false;
            $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostInvoiceAr  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
        }

        if (isset($readyToPostResponse['error'])) { //  ($readyToPostResponse !== '') TODO:: This should to be add with or condition
            $errorMessage = 'Error while making ar2 Apply split payment ready to post to sage';
            $message = 'readyToPostInvoiceAr failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }
        if ($isLiveApiCallStep4) {
            $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
        }
        info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr completed successfully');

        // 5
        $isLiveApiCallStep5 = true;
        $aRPostInvoices = SagePayloadFactory::aRPostInvoices(batchNumber: $batchNumber, type: $sageEntryType, extras: $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  aRPostInvoices  Sent Already for '.$quote->code);
            $isLiveApiCallStep5 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_4']]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR invoice batch '.$batchNumber.'  for '.$quote->code);
                $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$batchNumber.')', [], 'GET');
                $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                info('SAGE API :  Status of  AR invoice batch('.$batchNumber.') : '.$arInvoiceBatch['BatchStatus']);
                if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AR invoice batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostInvoices['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API :  Send aRPostInvoices  for '.$quote->code);
                $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making ar2 Apply split payment Posted to sage';
            $message = 'aRPostInvoices  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        } else {
            info('SAGE API : '.$quote->code.' : aRPostInvoices completed successfully');
            if ($isLiveApiCallStep5) {
                $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }
        }
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
        info('########## Start of Upfront createAPInvoicePrem for : '.$quote->code.' ##########');
        if (! $isTotalPriceZero) {
            $isLiveApiCallStep5 = true;
            $createAPInvoicePrem = SagePayloadFactory::createAPInvoicePrem(request: $sageRequest, type: $sageEntryType, reversalDetails: $reverseInvoiceDetails, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
                info('SAGE API :  createAPInvoicePrem  Sent Already for '.$quote->code);
                $isLiveApiCallStep5 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
            } else {
                info('SAGE API :  Send createAPInvoicePrem  for '.$quote->code);
                $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (! empty($postedResponse['BatchNumber'])) {
                info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep5) {
                    $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }

                $isLiveApiCallStep6 = true;
                $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType);
                if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                    info('SAGE API :  readyToPostInvoiceAP  Sent Already for '.$quote->code);
                    $isLiveApiCallStep6 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                } else {
                    info('SAGE API :  Send readyToPostInvoiceAP  for '.$quote->code);
                    $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    $errorMessage = 'Error while making AP invoice ready to post to sage';
                    $message = 'readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    info('SAGE API : '.$quote->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                    if ($isLiveApiCallStep6) {
                        $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }

                $isLiveApiCallStep7 = true;
                $aPPostInvoices = SagePayloadFactory::aPPostInvoices($postedResponse['BatchNumber'], type: $sageEntryType);
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                    info('SAGE API :  aPPostInvoices  Sent Already for '.$quote->code);
                    $isLiveApiCallStep7 = false;
                    $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
                } else {
                    $isAlreadyPosted = false;
                    if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                        info('SAGE API :  Check status of  AP invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                        $aPInvoiceBatch = $this->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                        $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                        info('SAGE API : Status of  AP invoice batch('.$postedResponse['BatchNumber'].'): '.$aPInvoiceBatch['BatchStatus']);

                        if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            info('SAGE API : AP invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                            $postedResponse = $aPPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }

                    if (! $isAlreadyPosted) {
                        info('SAGE API :  Send aPPostInvoices  for '.$quote->code);
                        $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                if (isset($postedResponse['error'])) {
                    $errorMessage = 'Error while making AP invoices Posted to sage';
                    $message = 'aPPostInvoices failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    info('SAGE API : '.$quote->code.' : aPPostInvoices completed successfully');
                    if ($isLiveApiCallStep7) {
                        $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }
            } else {
                $errorMessage = 'Ap invoice prem failed from sage';
                $message = 'createAPInvoicePrem  failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
        } else {
            info('########## skipping of createAPInvoicePrem for : '.$quote->code.' due to Zero Pricing ########## ');
        }
        info('########## End of Upfront createAPInvoicePrem for : '.$quote->code.' ##########');

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

        if ($sageEntryType == SageEnum::SCT_CORRECTION) {
            $totalSteps = 25;
            $stepsMapping = ['step_1' => 14, 'step_2' => 15, 'step_3' => 16, 'step_4' => 17];
        }

        info('########## Start of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');
        $isLiveApiCallStep6 = true;
        $createAPInvoicePrem = SagePayloadFactory::createAPInvoiceSplitPayments($sageRequest, $paymentSplits, $sageEntryType, $reverseInvoiceDetails, $extraDetails);
        if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  createAPInvoiceSplitPayments  Sent Already for '.$quote->code);
            $isLiveApiCallStep6 = false;
            $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
        } else {
            info('SAGE API :  Send createAPInvoiceSplitPayments  for '.$quote->code);
            $resp = $this->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if (! empty($postedResponse['BatchNumber'])) {
            $apBatchNumber = $postedResponse['BatchNumber'];
            $url = 'AP/APInvoiceBatches('.$apBatchNumber.')';
            info('SAGE API : '.$quote->code.' : createAPInvoiceSplitPayments - '.$apBatchNumber.' completed successfully');
            if ($isLiveApiCallStep6) {
                $this->logSageApiCall($createAPInvoicePrem, $postedResponse, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
            }

            if ($sageEntryType != SageEnum::SCT_REVERSAL) {
                info('SAGE API :  Prepare Patch payload for SpitPayments  for '.$quote->code);
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
                // 7
                $isLiveApiCallStep7 = true;
                if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                    info('SAGE API :  Patch Request  Sent Already for '.$quote->code);
                    $isLiveApiCallStep7 = false;
                    $postedResponse = ! empty($sageLogArray[$stepsMapping['step_2']]['response']) ? json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true) : [];
                } else {
                    info('SAGE API :  Send Patch Request  for '.$quote->code);
                    $resp = (new SageCustomApiService)->updateAPInvoicePaymentSchedule($postedResponse['BatchNumber'], $aPInvoicePaymentsSchedule);
                    $postedResponse['response'] = $resp;
                }

                $postedResponse['endPoint'] = $resp['url'] ?? $postedResponse['endPoint'] ?? null;
                $postedResponse['payload'] = $aPInvoicePaymentsSchedule;
                if (! $postedResponse['response']['status']) {
                    info('SAGE API : '.$quote->code.' : AP Patch Request failed '.json_encode($postedResponse['response']));
                    $postedResponse['sage_request_type'] = SageEnum::SRT_AP_SPPAY_PAY_SCDULE_PATCH;
                    $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;
                    $errorMessage = 'Error while making AP split payments patch to sage';
                    $message = 'AP Patch Request failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $postedResponse, $resp, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                }
                info('SAGE API : '.$quote->code.' : Patch Request completed successfully');
                if ($isLiveApiCallStep7) {
                    $postedResponse['sage_request_type'] = SageEnum::SRT_AP_SPPAY_PAY_SCDULE_PATCH;
                    $postedResponse['entry_type'] = $sageEntryType == SageEnum::SCT_CORRECTION ? SageEnum::SCT_CORRECTION : SageEnum::SCT_STRAIGHT;

                    $this->logSageApiCall($postedResponse, $postedResponse, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

            $isLiveApiCallStep8 = true;
            $readyToPostInvoiceAP = SagePayloadFactory::readyToPostInvoiceAP(batchNumber: $apBatchNumber, type: $sageEntryType);
            if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                info('SAGE API :  readyToPostInvoiceAP  Sent Already for '.$quote->code);
                $isLiveApiCallStep8 = false;
                $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
            } else {
                info('SAGE API :  Send readyToPostInvoiceAP  for '.$quote->code);
                $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAP['endPoint'], $readyToPostInvoiceAP['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $errorMessage = 'Error while making AP invoice ready to post to sage';
                $message = 'readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAP, $readyToPostResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            } else {
                info('SAGE API : '.$quote->code.' : readyToPostInvoiceAP - '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep8) {
                    $this->logSageApiCall($readyToPostInvoiceAP, $readyToPostResponse, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

            $isLiveApiCallStep9 = true;
            $aPPostInvoices = SagePayloadFactory::aPPostInvoices(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType);
            if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_SUCCESS) {
                info('SAGE API :  aPPostInvoices  Sent Already for '.$quote->code);
                $isLiveApiCallStep9 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_4']]['response'], true);
            } else {
                $isAlreadyPosted = false;
                if (isset($sageLogArray[$stepsMapping['step_4']]) && $sageLogArray[$stepsMapping['step_4']]['status'] == SageEnum::STATUS_FAIL) {
                    info('SAGE API :  Check status of  AP invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                    $aPInvoiceBatch = $this->postToSage300('AP/APInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                    $aPInvoiceBatch = json_decode($aPInvoiceBatch, true);

                    info('SAGE API :  Status of  AP invoice batch('.$postedResponse['BatchNumber'].') : '.$aPInvoiceBatch['BatchStatus']);
                    if ($aPInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                        info('SAGE API : AP invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                        $postedResponse = $aPPostInvoices['payload'];
                        $isAlreadyPosted = true;
                    }
                }

                if (! $isAlreadyPosted) {
                    info('SAGE API :  Send aPPostInvoices  for '.$quote->code);
                    $resp = $this->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);
                    $postedResponse = json_decode($resp, true);
                }
            }

            if (isset($postedResponse['error'])) {
                $errorMessage = 'Error while making AP invoices Posted to sage';
                $message = 'aPPostInvoices failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aPPostInvoices, $postedResponse, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            } else {
                info('SAGE API : '.$quote->code.' : aPPostInvoices completed successfully');
                if ($isLiveApiCallStep9) {
                    $this->logSageApiCall($aPPostInvoices, $postedResponse, $quote, $stepsMapping['step_4'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }
            }

        } else {
            $errorMessage = 'Ap invoice prem failed from sage';
            $message = 'createAPInvoicePrem  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createAPInvoicePrem, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
        }
        info('  ########## End of NON Upfront createAPInvoicePrem for : '.$quote->code.' ########## ');

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
            info('########## Start createARInvoiceDis for : '.$quote->code.' ##########');
            $isLiveApiCallStep10 = true;
            $createARInvoiceDis = SagePayloadFactory::createARInvoiceDis(request: $sageRequest, type: $sageEntryType, reversalDetails: $reverseInvoiceDetails, extras: $extraDetails);
            if (isset($sageLogArray[$stepsMapping['step_1']]) && $sageLogArray[$stepsMapping['step_1']]['status'] == SageEnum::STATUS_SUCCESS) {
                info('SAGE API:  createARInvoiceDis Sent Already for '.$quote->code);
                $isLiveApiCallStep10 = false;
                $postedResponse = json_decode($sageLogArray[$stepsMapping['step_1']]['response'], true);
            } else {
                info('SAGE API : Send createARInvoiceDis for '.$quote->code);
                $resp = $this->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                $postedResponse = json_decode($resp, true);
            }

            if (! empty($postedResponse['BatchNumber'])) {
                info('SAGE API : '.$quote->code.' : createARInvoiceDis - BatchNumber : '.$postedResponse['BatchNumber'].' completed successfully');
                if ($isLiveApiCallStep10) {
                    $this->logSageApiCall($createARInvoiceDis, $postedResponse, $quote, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                }

                $isLiveApiCallStep11 = true;
                $readyToPostInvoiceAr = SagePayloadFactory::readyToPostInvoiceAr(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
                if (isset($sageLogArray[$stepsMapping['step_2']]) && $sageLogArray[$stepsMapping['step_2']]['status'] == SageEnum::STATUS_SUCCESS) {
                    info('SAGE API :  readyToPostInvoiceAr  Sent Already for '.$quote->code);
                    $isLiveApiCallStep11 = false;
                    $readyToPostResponse = json_decode($sageLogArray[$stepsMapping['step_2']]['response'], true);
                } else {
                    info('SAGE API :  Send readyToPostInvoiceAr  for '.$quote->code);
                    $readyToPostResponse = $this->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');
                }

                if ($readyToPostResponse !== '') {
                    $errorMessage = 'Error while making Ar discount invoice ready to post to sage';
                    $message = 'readyToPostInvoiceAr failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostInvoiceAr, $readyToPostResponse, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr completed successfully');
                    if ($isLiveApiCallStep11) {
                        $this->logSageApiCall($readyToPostInvoiceAr, $readyToPostResponse, $quote, $stepsMapping['step_2'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }

                $isLiveApiCallStep12 = true;
                $aRPostInvoices = SagePayloadFactory::aRPostInvoices(batchNumber: $postedResponse['BatchNumber'], type: $sageEntryType, extras: $extraDetails);
                if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_SUCCESS) {
                    info('SAGE API :  aRPostInvoices  Sent Already for '.$quote->code);
                    $isLiveApiCallStep12 = false;
                    $postedResponse = json_decode($sageLogArray[$stepsMapping['step_3']]['response'], true);
                } else {
                    $isAlreadyPosted = false;
                    if (isset($sageLogArray[$stepsMapping['step_3']]) && $sageLogArray[$stepsMapping['step_3']]['status'] == SageEnum::STATUS_FAIL) {
                        info('SAGE API :  Check status of  AR invoice batch '.$postedResponse['BatchNumber'].'  for '.$quote->code);
                        $arInvoiceBatch = $this->postToSage300('AR/ARInvoiceBatches('.$postedResponse['BatchNumber'].')', [], 'GET');
                        $arInvoiceBatch = json_decode($arInvoiceBatch, true);

                        info('SAGE API :  Status of  AR invoice batch('.$postedResponse['BatchNumber'].') : '.$arInvoiceBatch['BatchStatus']);
                        if ($arInvoiceBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                            info('SAGE API : AR invoice batch '.$postedResponse['BatchNumber'].' already posted for '.$quote->code);
                            $postedResponse = $aRPostInvoices['payload'];
                            $isAlreadyPosted = true;
                        }
                    }

                    if (! $isAlreadyPosted) {
                        info('SAGE API :  Send aRPostInvoices  for '.$quote->code);
                        $resp = $this->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);
                        $postedResponse = json_decode($resp, true);
                    }
                }

                if (isset($postedResponse['error'])) {
                    $errorMessage = 'Error while making Ar discount invoice Posted to sage';
                    $message = ' aRPostInvoices failed';

                    return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostInvoices, $postedResponse, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
                } else {
                    info('SAGE API : '.$quote->code.' : aRPostInvoices  completed successfully');
                    if ($isLiveApiCallStep12) {
                        $this->logSageApiCall($aRPostInvoices, $postedResponse, $quote, $stepsMapping['step_3'], $totalSteps, SageEnum::STATUS_SUCCESS, $userId);
                    }
                }
            } else {
                $errorMessage = 'Ar discount invoice failed from sage';
                $message = ' createARInvoiceDis failed';

                return $this->logErrorAndReturn([$quote, $message, $errorMessage, $createARInvoiceDis, $postedResponse, $stepsMapping['step_1'], $totalSteps, SageEnum::STATUS_FAIL, $userId]);
            }
            info('  ########## End createARInvoiceDis for : '.$quote->code.' ########## ');
        }

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'AR Discount invoice created on sage';

        return $returnMessage;
    }

    public function applyPaymentInvoices($sageRequestDataArray)
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $isTotalPriceZero = $payment->total_price == 0;

        /* Start: Temporary code for historic data to allow book polciy after m2 launch */
        $isQuoteFallUnderSkippableCriteria = $this->skipApplyPrepaymentsForSpecificLeads($quote, $payment, $paymentSplits);
        if ($isQuoteFallUnderSkippableCriteria['status']) {
            return $isQuoteFallUnderSkippableCriteria;
        }
        /* End: Temporary code for historic data to allow book polciy after m2 launch */

        /* applyPaymentInvoices */
        $isTransactionPaidAndFrequencyUpfront = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::UPFRONT;

        if ($isTransactionPaidAndFrequencyUpfront && ! $isTotalPriceZero) {
            return $this->applyUpfrontPaymentInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        } elseif ($isTotalPriceZero) {
            info('########## applyUpfrontPaymentInvoices skipped  for : '.$quote->code.' due to zero price ########## ');
        }

        $isFrequencySplitAndFirstChildPaymentPaid = $sageRequest->invoicePaymentStatus == PaymentStatusEnum::PAID && $payment->frequency == PaymentFrequency::SPLIT_PAYMENTS;

        if ($isFrequencySplitAndFirstChildPaymentPaid) {
            return $this->applySplitPaymentInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        }

        $isFrequencyUpfrontOrSplit = in_array($payment->frequency, [PaymentFrequency::UPFRONT, PaymentFrequency::SPLIT_PAYMENTS]);
        $isFirstPaymentPaidOrCaptured = in_array($paymentSplits[0]['payment_status_id'], [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]);
        if (! $isFrequencyUpfrontOrSplit && $isFirstPaymentPaidOrCaptured) {
            return $this->applyNonSplitNonUpfrontPaymentInvoices([$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray]);
        }

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Apply Prepayment completed';

        return $returnMessage;

    }

    private function applyUpfrontPaymentInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        info('########## Start applypaymentInvoices for : '.$quote->code.' ##########');
        $totalSteps = 15;
        // 13
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API : createPaymentReceiptOneInvoice  Sent Already for '.$quote->code);
            $isLiveApiCallStep13 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send createPaymentReceiptOneInvoice  for '.$quote->code);
            //            TODO:: This need to be defined in top but isPostAllSplitPayment (Last param) need to be handled
            $payLoadOptions = SagePayloadFactory::createPaymentReceiptOneInvoice($quote, $sageRequest->customerId, $payment, $paymentSplits, true);
            $resp = $this->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $postedResponse = json_decode($resp, true);
        }

        if ($isLiveApiCallStep13) {
            $this->logSageApiCall($payLoadOptions, $postedResponse, $quote, $currentStep, $totalSteps);
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making split prepayments to sage';
            $message = 'createPaymentReceiptOneInvoice failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $payLoadOptions, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }

        $batchNumber = $postedResponse['BatchNumber'];
        info('SAGE API : '.$quote->code.' : createPaymentReceiptOneInvoice  - BatchNumber : '.$batchNumber.' completed successfully');
        // 14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptAr failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            info('SAGE API : '.$quote->code.' : readyToPostReceiptAr completed successfully');
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
            }
        }

        // 15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                info('SAGE API :  Status of  AR Prepayment Receipts batch('.$batchNumber.') : '.$aRReceiptBatch['BatchStatus']);
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AR Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API :  Send aRPostReceipts  for '.$quote->code);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = 'aRPostReceipts failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        info('SAGE API : '.$quote->code.' : aRPostReceipts completed successfully');
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
        }
        info('########## End applypaymentInvoices for : '.$quote->code.' ##########');
        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }

    private function applySplitPaymentInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        info('########## Start arSplitPrepaymentPayload for : '.$quote->code.' ##########');
        $totalSteps = 15;

        // 12
        $currentStep = 13;
        $isLiveApiCallStep13 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  arSplitPrepaymentPayload  Sent Already for '.$quote->code);
            $isLiveApiCallStep13 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send arSplitPrepaymentPayload  for '.$quote->code);
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
            $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps);
        }

        $batchNumber = $response['BatchNumber'];
        info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - BatchNumber : '.$batchNumber.' completed successfully');
        // 14
        $currentStep = 14;
        $isLiveApiCallStep14 = true;
        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep14 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = ' readyToPostReceiptAr - BatchNumber : '.$batchNumber.' failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            info('SAGE API : '.$quote->code.' :  readyToPostReceiptAr - BatchNumber : '.$batchNumber.' completed successfully');
            if ($isLiveApiCallStep14) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
            }
        }

        // 15
        $currentStep = 15;
        $isLiveApiCallStep15 = true;
        $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API : aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep15 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {

            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                info('SAGE API :  Status of  AR Prepayment Receipts batch('.$batchNumber.') : '.$aRReceiptBatch['BatchStatus']);
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API : Send aRPostReceipts  for '.$quote->code);
                $resp = $this->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($resp, true);
            }
        }

        if (isset($postedResponse['error'])) {
            $errorMessage = 'Error while making Apply payment Posted to sage';
            $message = ' aRPostReceipts failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $aRPostReceipts, $postedResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        }
        info('SAGE API : '.$quote->code.' : aRPostReceipts completed successfully');
        //            TODO:: This undefined variable need to defined properly
        if ($isLiveApiCallStep15) {
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
        }
        info('########## End arSplitPrepaymentPayload for : '.$quote->code.' ##########');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }

    private function applyNonSplitNonUpfrontPaymentInvoices($sageRequestDataArray)
    {
        [$sageRequest, $quote, $payment, $paymentSplits, $sageLogArray] = $sageRequestDataArray;
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];

        info('########## Start arSplitPrepaymentPayload for : '.$quote->code.' ##########');
        $totalSteps = 18;

        // 15
        $currentStep = 16;
        $isLiveApiCallStep16 = true;
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API : arSplitPrepaymentPayload  Sent Already for '.$quote->code);
            $isLiveApiCallStep16 = false;
            $response = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API : Send arSplitPrepaymentPayload  for '.$quote->code);
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
            $this->logSageApiCall($readyToPostReceiptAr, $response, $quote, $currentStep, $totalSteps);

        }
        $batchNumber = $response['BatchNumber'];
        info('SAGE API : '.$quote->code.' : readyToPostInvoiceAr - BatchNumber : '.$batchNumber.' completed successfully');
        // 16
        $currentStep = 17;
        $isLiveApiCallStep17 = true;
        $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptAr($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  readyToPostReceiptAr  Sent Already for '.$quote->code);
            $isLiveApiCallStep17 = false;
            $readyToPostResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            info('SAGE API :  Send readyToPostReceiptAr  for '.$quote->code);
            $readyToPostResponse = $this->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
        }

        if ($readyToPostResponse !== '') {
            $errorMessage = 'Error while making Apply payment ready to post to sage';
            $message = 'readyToPostReceiptAr  failed';

            return $this->logErrorAndReturn([$quote, $message, $errorMessage, $readyToPostReceiptAr, $readyToPostResponse, $currentStep, $totalSteps, SageEnum::STATUS_FAIL]);
        } else {
            info('SAGE API : '.$quote->code.' : readyToPostReceiptAr completed successfully');
            if ($isLiveApiCallStep17) {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $quote, $currentStep, $totalSteps);
            }
        }

        // 17
        $currentStep = 18;
        $isLiveApiCallStep18 = true;
        $aRPostReceipts = SagePayloadFactory::aRPostReceipts($batchNumber);
        if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_SUCCESS) {
            info('SAGE API :  aRPostReceipts  Sent Already for '.$quote->code);
            $isLiveApiCallStep18 = false;
            $postedResponse = json_decode($sageLogArray[$currentStep]['response'], true);
        } else {
            $isAlreadyPosted = false;
            if (isset($sageLogArray[$currentStep]) && $sageLogArray[$currentStep]['status'] == SageEnum::STATUS_FAIL) {
                info('SAGE API :  Check status of  AR Prepayment Receipts batch '.$batchNumber.'  for '.$quote->code);
                $aRReceiptBatch = $this->postToSage300("AR/ARReceiptAndAdjustmentBatches(BatchRecordType='CA',BatchNumber=".$batchNumber.')', [], 'GET');
                $aRReceiptBatch = json_decode($aRReceiptBatch, true);

                info('SAGE API :  Status of  AR Prepayment Receipts batch('.$batchNumber.') '.$aRReceiptBatch['BatchStatus']);
                if ($aRReceiptBatch['BatchStatus'] == SageEnum::SAGE_STATUS_POSTED) {
                    info('SAGE API : AP Prepayment Receipts batch '.$batchNumber.' already posted for '.$quote->code);
                    $postedResponse = $aRPostReceipts['payload'];
                    $isAlreadyPosted = true;
                }
            }

            if (! $isAlreadyPosted) {
                info('SAGE API :  Send aRPostReceipts  for '.$quote->code);
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
            $this->logSageApiCall($aRPostReceipts, $postedResponse, $quote, $currentStep, $totalSteps);
        }
        info('SAGE API : '.$quote->code.' : aRPostReceipts - BatchNumber '.$batchNumber.' completed successfully');
        info('  ########## End arSplitPrepaymentPayload for : '.$quote->code.' ########## ');

        $returnMessage['status'] = true;
        $returnMessage['message'] = 'Prepayments applied on sage';

        return $returnMessage;
    }

    private function logErrorAndReturn($logDataArray, $storeSageApiLog = true): array
    {
        $returnMessage = ['status' => false, 'message' => null, 'error' => null];
        $logDataArray = array_pad($logDataArray, 9, null);
        [$quote, $message, $errorMessage, $payload, $response, $currentStep, $totalSteps, $status, $userId] = $logDataArray;

        info("SAGE API : $quote->code : $message");
        info("SAGE API : $quote->code : $errorMessage");

        $returnMessage['message'] = $errorMessage;
        $responseArray = $this->convertResponseToArray($response);
        $sageErrorMessage = $responseArray['error']['message']['value'] ?? $responseArray['error'] ?? null;
        info("SAGE API : $quote->code  : ".json_encode($sageErrorMessage));
        $returnMessage['error'] = $sageErrorMessage;
        if ($this->sageHasProcessingConflict($sageErrorMessage)) {
            $returnMessage['message'] = SageEnum::SAGE_PROCESSING_CONFLICT_MESSAGE;
        }

        if ($storeSageApiLog) {
            $this->logSageApiCall($payload, $response, $quote, $currentStep, $totalSteps, $status, $userId);
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
                    info('  ########## applyUpfrontPaymentInvoices skipped  for : '.$quote->code.' due to sage receipt not generated on sage ########## ');
                    $returnMessage['status'] = true;
                    $returnMessage['message'] = 'Apply Prepayment skipped due to sage receipt not generated on sage';

                    return $returnMessage;
                }

                return $returnMessage;
            } else {
                $firstPaymentSplitWithNoSageReceipt = $paymentSplits->where('sr_no', 1)->whereNull('sage_reciept_id')->count();
                if ($firstPaymentSplitWithNoSageReceipt) {
                    info('  ########## applyUpfrontPaymentInvoices skipped  for : '.$quote->code.' due to sage receipt not generated on sage ########## ');
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
        info($logFor.' : updateSageProcessStatus - ID : '.$sageProcess->id.' - Status : '.$status);
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

        info('Policy Book : updateAndLogQuoteStatus - Code : '.$quote->code.', - Status : '.$newQuoteStatusId);

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
            info('Policy Book : updateAndLogQuoteStatus - Code : '.$quote->code.' start updateStatusesAndAllocate Quote Status ID : '.$quote->quote_status_id);
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
        return str_contains($sageErrorMessage, 'Processing conflict') || str_contains($sageErrorMessage, 'Post in Progress');
    }

    public function scheduleSageProcesses($insurerId = null): void
    {
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

                    info('cmd:SageProcessesCommand - Processing Sage Process ID: '.$sageProcess->id.' for Insurance Provider ID: '.$sageProcess->insurance_provider_id);

                    $sageProcessRequest = json_decode($sageProcess->request);
                    $sageRequest = $sageProcessRequest->sagePayload;
                    $request = $sageProcessRequest->requestPayload;

                    if ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_BOOK_POLICY_REQUEST) {
                        $quote = $this->getQuoteObject($request->model_type, $sageProcess->model_id);
                        BookPolicyOnSageJob::dispatch($sageRequest, $quote, $request, $sageProcess)->onQueue('insly');
                    } elseif ($sageRequest->sageProcessRequestType == SageEnum::SAGE_PROCESS_SEND_UPDATE_REQUEST) {
                        $model = $sageProcess->model;
                        SendUpdateSageJob::dispatch($request, $model, $sageRequest, $sageProcess)->onQueue('insly');
                    }
                }
            } else {
                info('cmd:SageProcessesCommand - No Sage Process meet the selection criteria / already sage processes are being processed against all insurance providers');
            }
            $sageProcessCommandLock->release();
        } else {
            info('cmd:SageProcessesCommand - Sage Policy or Endorsements Booking Command is already running, skipping execution.');
        }
    }

    public function updateStatusesAndAllocate($quote, $quoteTypeId)
    {
        info('Policy Book : Quote '.$quote?->code.' : '.__FUNCTION__.' - start');

        $policyIssuanceAutomation = $quote?->policyIssuance;

        if ($policyIssuanceAutomation) {
            $quoteType = $policyIssuanceAutomation->quote_type;
            $insuranceProvider = $policyIssuanceAutomation->insuranceProvider;
            $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider?->code);

            /* if the Policy Issuance exist for the Insurer and LOB than assign the Advisor */
            if ($insuranceProviderAutomation) {
                info('Policy Book : Quote '.$quote?->code.' : '.__FUNCTION__.' - assign advisor and update insurer and api issuance status of quote');
                $insuranceProviderAutomation?->updateQuoteApiIssuanceStatusAndAllocate($quote);
            } else {
                info('Policy Book : Quote '.$quote?->code.' : '.__FUNCTION__.' Insurer : '.$insuranceProvider?->code.'automation class not found');
            }
        } else {
            info('Policy Book : Quote '.$quote?->code.' : policy issuance automation not found');
        }

        info('Policy Book : Quote '.$quote?->code.' : '.__FUNCTION__.' - end');
    }

    public function isSageEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);
    }
    public function isSageRetryTimeoutEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_TIMEOUT_RETRY_ENABLED);
    }

}
