<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\InsuranceProvider;
use App\Models\InsurerRequestResponse;
use App\Models\QuoteDocument;
use App\Repositories\EmbeddedProductRepository;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Enums\SukoonMedexEnum;
use App\Enums\EmbeddedTransactionEnum;

class SukoonMedexService
{
    /**
     * Create a new class instance.
     */
    private $sukoonRequestUrl;
    private $sessionId;
    private $currentQuote;
    private $quoteTypeId;
    private $productSlug;
    private $quotePolicy;
    private $certificateNumber;
    private $policyStatus;
    private $paymentGateway;
    private $paymentToken;
    private $transaction;

    private $paymentPlan;
    private $amountDisclaimerText;
    private array $sukoonReqDocTypeCodes;
    private $providerId;

    private string $logPrefix = 'Sukoon Medex Service:';
    private array $errorMessages = [];
    
    public function __construct() {
        $this->sukoonRequestUrl = config('constants.SUKOON_API_URL')."/api/v".config('constants.SUKOON_API_VERSION');
        $this->sukoonReqDocTypeCodes = [QuoteDocumentsEnum::CAR_TAX_INVOICE, QuoteDocumentsEnum::POLICY_SCHEDULE, QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER];
    }

    private function viewQuotePolicy()
    {
        try {
            $headers = ['x-session-id' => $this->sessionId, 'Content-Type' => 'application/json', 'Accept' => 'application/json'];
            $response = $this->request("/policy/{$this->certificateNumber}", 'get', headers: $headers)->json();
            return $response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function initiatePurchaseFlow($quote, $quoteTypeId, $transaction)
    {
        try {
            $this->currentQuote = $quote;
            $this->quoteTypeId = $quoteTypeId;
            $this->transaction = $transaction;

            $this->policyStatus = $transaction->policy_status ?? '';
            $this->quotePolicy = $transaction->quote_policy ?? null;
            $this->certificateNumber = $transaction->certificate_number ?? null;

            LoggerService::startQuoteLogging($this->currentQuote);

            if (!in_array($this->quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike]))
                throw new Exception('Only (Car / Bike) LOB are eligible');

            $this->validateCustomerDetails($this->currentQuote);

            if(empty($this->sessionId))
                $this->login();

            $this->productSlug = ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_MEDEX_PRODUCT_SLUG)->value('value');
            $this->paymentGateway = ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PAYMENT_GATEWAY)->value('value');
            $this->providerId = InsuranceProvider::where('code', InsuranceProvidersEnum::OIC)->value('id');

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function syncSukoonData($transaction, $data)
    {
        $updateableData = collect($data)->only('quote_policy', 'certificate_number');

        if(!empty($data['policy_status'] ?? null))
            $this->policyStatus = $updateableData['policy_status'] = $data['policy_status'];

        $transaction->update($updateableData->toArray());

        !empty($updateableData['quote_policy'] ?? null) && $this->quotePolicy = $updateableData['quote_policy'] ?? null;
        !empty($updateableData['certificate_number'] ?? null) && $this->certificateNumber = $updateableData['certificate_number'] ?? null;

        !empty($data['payment_plan'] ?? null) && $this->paymentPlan = $data['payment_plan'] ?? null;
        !empty($data['amount_disclaimer_text'] ?? null) && $this->amountDisclaimerText = $data['amount_disclaimer_text'] ?? null;
        !empty($data['payment_token'] ?? null) && $this->paymentToken = $data['payment_token'] ?? null;

        return $updateableData;
    }

    /**
     * Processes the SukoonMedex Purchase Flow for the given quote and transaction.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $transaction  The transaction object.
     * @return void
     */
    public function processPurchaseFlow()
    {
        try {
            if($this->policyStatus == EmbeddedTransactionEnum::STATUS_BOOKED) {
                LoggerService::info("{$this->logPrefix} Already booked, skipping purchase flow");
                return false;
            }

            // Skipable Steps (#1-init, #3-getForm, #5-preReviewSubmittedData, #9-listPaymentGateways)
            // STEP #2 login (trigger by initiatePurchaseFlow)

            if(!EmbeddedTransactionEnum::checkPolicyStatusPassed($this->policyStatus, EmbeddedTransactionEnum::STATUS_QUOTED)) {
                // STEP #4 submitPersonalDetail
                $profileDetailResponse = $this->submitPersonalDetail($this->prepareUserDetails($this->currentQuote));
                $this->syncSukoonData($this->transaction, $profileDetailResponse);

                // STEP #6 submitPlan
                $submitPlanResponse = $this->submitPlan($this->prepareAdditionalData());
                $this->syncSukoonData($this->transaction, $submitPlanResponse);

                // STEP #7 reviewSubmittedData
                $reviewSubmittedDataResponse = $this->reviewSubmittedData();
                $this->syncSukoonData($this->transaction, $reviewSubmittedDataResponse);

                // STEP #8 confirmSubmittedData
                $this->confirmSubmittedData();
            }

            if(!EmbeddedTransactionEnum::checkPolicyStatusPassed($this->policyStatus, EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED)) {
                // STEP #10 initiatePaymentProcess
                $initPaymentResponse = $this->initiatePaymentProcess();
                $this->syncSukoonData($this->transaction, $initPaymentResponse);

                // STEP #11 completeInvoicePayment
                $invoicePaymentResponse = $this->completeInvoicePayment($this->transaction);
                $this->syncSukoonData($this->transaction, $invoicePaymentResponse);
                $this->transaction->documents()->whereIn('document_type_code', $this->sukoonReqDocTypeCodes)->delete();
                $this->transaction->load('documents');
            }

            $missingReqDocTypes = $this->getMissingReqDocTypes();

            // STEP #12 getPolicyScheduleCoi 
            if(in_array(QuoteDocumentsEnum::POLICY_SCHEDULE, $missingReqDocTypes))
                $this->getPolicyScheduleCoi();

            // STEP #13 getCustomerTaxInvoice
            if(in_array(QuoteDocumentsEnum::CAR_TAX_INVOICE, $missingReqDocTypes)) {
                empty($this->paymentToken) && $this->fetchPaymentToken();
                $this->getCustomerTaxInvoice();
            }

            if(!empty($missingReqDocTypes)) {
                // STEP #14 listGeneratedDocument
                $listGeneratedDocumentResponse = $this->listGeneratedDocument();

                // STEP #15 downloadDocument
                $generatedDocCount = count($listGeneratedDocumentResponse['documents'] ?? []);
                if($generatedDocCount > 0) {
                    $savedDocs = $this->syncGeneratedDocuments($listGeneratedDocumentResponse['documents'], $this->currentQuote, $this->transaction);

                    $skippedDocCount = count($savedDocs['skipped'] ?? []);
                    $createdDocCount = count($savedDocs['created'] ?? []);
                    $updatedDocCount = count($savedDocs['updated'] ?? []);

                    LoggerService::info("{$this->logPrefix} Sync & Saved Documents: ".($createdDocCount + $updatedDocCount)." out of {$generatedDocCount}, ".
                        "created: {$createdDocCount}, updated: {$updatedDocCount}, skipped: {$skippedDocCount}", extra: ['docs' => $savedDocs]);

                    if(($createdDocCount + $updatedDocCount) > 0)
                        $this->transaction->load('documents');
                }
            }

            // STEP #16 viewQuotePolicy
            $quotePolicyResponse = $this->viewQuotePolicy();
            $this->updateTransaction($this->transaction, $quotePolicyResponse);

            if(EmbeddedTransactionEnum::checkPolicyStatusPassed($this->policyStatus, EmbeddedTransactionEnum::STATUS_BOOKED) && !$this->transaction->is_document_sent) {
                EmbeddedProductRepository::sendDocument([
                    'epId' => $this->transaction->product->embeddedProduct->id ?? null,
                    'modelType' => QuoteTypes::getName($this->quoteTypeId)->value,
                    'quoteId' => $this->currentQuote->id,
                ]);
            }

        } catch (Exception $e) {
            $this->logFailure("{$this->logPrefix} processPurchaseFlow Failed", $e->getMessage(), [
                'quote_uuid' => $this->currentQuote->uuid ?? null,
                'error_messages' => $this->errorMessages
            ]);
        }
    }

    public function fetchPaymentToken()
    {
        try {
            // STEP #16 viewQuotePolicy
            $viewQuotePolicyResponse = $this->viewQuotePolicy();
            return $this->paymentToken = $viewQuotePolicyResponse['payments'][0]['token'] ?? null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function syncSukoonDocuments()
    {
        try {
            LoggerService::info("{$this->logPrefix} syncSukoonDocuments");
            // STEP #14 listGeneratedDocument
            $listGeneratedDocumentResponse = $this->listGeneratedDocument();

            // STEP #15 downloadDocument
            $this->syncGeneratedDocuments($listGeneratedDocumentResponse['documents'], $this->currentQuote, $this->transaction);
        } catch (Exception $e) {
            $this->logFailure("{$this->logPrefix} syncSukoonDocuments Failed", $e->getMessage(), [
                'quote_uuid' => $this->currentQuote->uuid ?? null,
                'error_messages' => !empty($this->errorMessages) ? $this->errorMessages : $e->getMessage()
            ]);
        }
    }

    /**
     * Updates the transaction with the given transaction details.
     *
     * @param  mixed  $transaction  The transaction object.
     * @param  array  $transactionDetail  The transaction details array.
     * @return void
     */
    private function updateTransaction($transaction, $transactionDetail)
    {
        $paymentData = $transactionDetail['payments'][0];
        $additionalData = $transactionDetail['additional_data'];
        $commissionAmount = floatval($additionalData['broker_commission_amount'] ?? 0) ? (float) ($additionalData['broker_commission_amount'] ?? 0) : (int) ($additionalData['broker_commission_amount'] ?? 0);
        $commissionVat = floatval($additionalData['broker_commission_vat_amount'] ?? 0) ? (float) ($additionalData['broker_commission_vat_amount'] ?? 0) : (int) ($additionalData['broker_commission_vat_amount'] ?? 0);

        $data = [
            'certificate_number' => $this->certificateNumber,
            'tax_invoice_no' => $additionalData['tax_invoice_document_number'] ?? null,
            'tax_invoice_buyer_no' => $additionalData['tax_invoice_buyer_document_number'] ?? null,
            'credit_note_no' => $additionalData['credit_note_document_number'] ?? null,
            'credit_note_buyer_no' => $additionalData['credit_note_buyer_document_number'] ?? null,
            'commission_with_vat' => $commissionAmount + $commissionVat ?? null,
            'commission_without_vat' => $commissionAmount,
            'policy_price' => $paymentData['amount_breakdown']['policy_price'] ?? null,
        ];

        if(!empty($paymentData['status']))
            $this->policyStatus = $data['policy_status'] = $paymentData['status']; // SukoonPurchaseFlowEnum::STATUS_PAYMENT_SUCCEED

        // Make sure no any required documents are missing & policyStatus is payment_succeeded
        if(empty($this->getMissingReqDocTypes()) && $this->policyStatus == EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED)
            $this->policyStatus = $data['policy_status'] = EmbeddedTransactionEnum::STATUS_BOOKED;

        LoggerService::info("{$this->logPrefix} policyStatus: {$this->policyStatus}");
        return $transaction->update($data);
    }

    // Check missing required documents types
    public function getMissingReqDocTypes()
    {
        return array_diff($this->sukoonReqDocTypeCodes, $this->transaction->documents->pluck('document_type_code')->toArray());
    }

    /**
     * Makes an HTTP request to the specified endpoint with the given method, data, and headers.
     *
     * @param  string  $endPoint  The API endpoint.
     * @param  string  $method  The HTTP method (default is 'post').
     * @param  array  $data  The data to send with the request.
     * @param  array  $headers  The headers to include with the request.
     * @return mixed The response from the API.
     */
    private function request($endPoint, $method = 'post', $payload = [], $headers = [])
    {
        $sukoonEndPoint = $this->sukoonRequestUrl.$endPoint;
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $parentFunction = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'Unknown';

        $responseData = null;

        try {
            $client = Http::withHeaders($headers);
            $response = $client->withBody(json_encode($payload), 'application/json')->send($method, $sukoonEndPoint)->onError(function ($response) use($payload, $endPoint, $parentFunction) {
                $contentType = $response->header('Content-Type');

                if (str_contains($contentType, 'application/json')) {
                    $this->fetchErrors($response->json());
                    throw new Exception("{$this->logPrefix} API Request Exception");
                } else {
                    $this->logRequest('failed', "API Exception Successful, Content Type: {$contentType}", $payload, $endPoint, $response->body(), $parentFunction);
                    return $response;
                }
            });

            $contentType = $response->header('Content-Type');

            if (!str_contains($contentType, 'application/json')) {
                $this->logRequest('passed', "Request Successful, Content Type: {$contentType}", $payload, $endPoint, parentFunction: $parentFunction);
                return $response;
            }

            $responseData = $response->json();

            if($responseData['has_errors'] ?? null) {
                $this->fetchErrors($responseData);
                $this->logRequest('failed', 'Request Error', $payload, $endPoint, $responseData, $parentFunction);
                return $response;
            }

            $this->logRequest('passed', 'Request Successful', $payload, $endPoint, $responseData, $parentFunction);
            return $response;

        } catch (Exception $e) {
            $this->logRequest('failed', $e->getMessage(), $payload, $endPoint, $responseData, $parentFunction);
            throw $e;
        }
    }
    
    /**
     * Logs the request details for debugging and auditing purposes.
     *
     * @param  string  $action  The action being logged.
     * @param  array  $data  The data associated with the action.
     * @return void
     */
    private function logRequest($status, $message, $data, $endPoint = '', $response = '', $parentFunction = '')
    {
        // Truncate response if it's too large
        $maxTextLength = 65535; // The maximum length for MySQL TEXT type

        // Ensure response is a JSON string
        $response = is_array($response) ? json_encode(['error_messages' => $this->errorMessages, ...$response]) : $response;

        // Check if the response exceeds the maximum length
        if (strlen($response) > $maxTextLength) {
            // Save the large response to a file
            $responseFilePath = storage_path('logs/response_sukoon_medex_'.uniqid().'.json');
            file_put_contents($responseFilePath, $response);
            $response = 'Response too large, saved to: '.$responseFilePath;
        }

        // This below unsetRelation is used to avoid the long response data in the log
        if ($this->currentQuote->relationLoaded('embeddedTransactions')) {
            foreach ($this->currentQuote->embeddedTransactions as $transaction) {
                if ($transaction->relationLoaded('product')) {
                    if ($transaction->product->relationLoaded('embeddedProduct')) {
                        $transaction->product->unsetRelation('embeddedProduct');
                    }
                    $transaction->unsetRelation('product');
                }
            }
        }

        if ($this->currentQuote->relationLoaded('emirate')) {
            $this->currentQuote->unsetRelation('emirate');
        }

        if ($this->currentQuote->relationLoaded('customer')) {
            $this->currentQuote->unsetRelation('customer');
        }

        if ($this->currentQuote->relationLoaded('quoteRequestEntityMapping')) {
            $this->currentQuote->unsetRelation('quoteRequestEntityMapping');
        }

        $logData = [
            'status' => $status,
            'execution_method' => $parentFunction,
            'quote_uuid' => $this->currentQuote->uuid,
            'call_type' => 'EmbeddedProduct',
            'provider_id' => $this->providerId,
        ];

        $extraLog = [
            'request' => is_array($data) ? json_encode($data) : $data,
            'response' => $response,
            'quote_data' => json_encode($this->currentQuote),
        ];

        InsurerRequestResponse::create([...$logData, ...$extraLog]);

        $response = json_decode($response) ? ((array) json_decode($response)) : $response;
        if(is_array($response)) {
            $pickedData = collect($response)->only('success', 'status', 'has_errors', 'policy_number', 'policy_status')->toArray();
            $logData = (array) [...$logData, ...$pickedData];
            $logData['error_messages'] = $this->errorMessages;
        } else {
            $logData = (array) [...$logData, 'response' => $response];
        }

        $stepNumber = SukoonMedexEnum::getStepNumber($parentFunction);
        LoggerService::info("{$this->logPrefix} API {$status} Step: #{$stepNumber} {$parentFunction}", context: ['message' => $message, 'endPoint' => Str::limit($endPoint ?? '', 50), ...$logData]);
    }

    /**
     * Logs the failure of a process with the given message and data.
     *
     * @param  string  $process  The name of the process.
     * @param  string  $message  The failure message.
     * @param  array  $data  The data related to the failure.
     * @return void
     */
    private function logFailure($operation, $message, $data = [])
    {
        LoggerService::info($this->logPrefix.' Failure', context: [
            'operation' => $operation,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Validates the customer details for the given quote.
     *
     * @param  mixed  $quote  The quote object.
     * @return void
     *
     * @throws Exception If validation fails.
     */
    public function validateCustomerDetails($quote)
    {
        $latestInsuredData = $quote->latestInsured;
        $insuredKyc = $latestInsuredData?->insuredKyc;
        $customerType = $latestInsuredData?->customer_type;
        $idType = $insuredKyc?->id_type;

        if (($customerType != CustomerTypeEnum::Individual || $idType != 'emiratesId') && ! $this->validateCustomerKycDetail(
            $insuredKyc?->id_number,
            $insuredKyc?->id_expiry_date,
            $insuredKyc?->residential_address
        )) {
            throw new Exception('Address cannot be empty, Invalid Emirates ID or Expiry Date');
        }
    }

    private function validateCustomerKycDetail($idNumber, $IdExpiryDate, $address)
    {
        $patternOfEID = '/^784-[0-9]{4}-[0-9]{7}-[0-9]{1}$/';

        return preg_match($patternOfEID, $idNumber) && $IdExpiryDate >= Carbon::now() && !empty($address);
    }
    
    /**
     * Prepares the user details array for the given quote and transaction.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $transaction  The transaction object.
     * @return array The prepared user details.
     */
    private function prepareUserDetails($quote)
    {
        $latestInsuredData = $quote->latestInsured;
        $insuredKyc = $latestInsuredData?->insuredKyc;

        if (! empty($quote->quoteRequestEntityMapping)) {
            $firstName = $quote->first_name ?? '';
            $lastName = $quote->last_name ?? '';
        } else {
            $firstName = ($insuredKyc?->first_name ?? $quote->customer?->insured_first_name) ?? '';
            $lastName = ($insuredKyc?->last_name ?? $quote->customer?->insured_last_name) ?? '';
        }

        $quoteType = $quote->quote_type_id ?? null;
        $emirate = $quoteType == QuoteTypeId::Bike ? ($quote->bikeQuote->emirates ?? null) : ($quote->emirate ?? null);

        return [
            'form_name' => 'personal_details',
            "title" => $latestInsuredData->gender == 'Male' ? "Mr" : 'Ms',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'mobile' => '+9710502732524', // '+971505027325',
            'email' => 'hitesh.motwani@insurancemarket.ae',
            'nationality' => 'AE',
            'emirate' => $emirate->text ?? '',
            'emirates_id_number' => $insuredKyc?->id_type == 'emiratesId' ? $insuredKyc?->id_number : '', //'784-1989-8057715-1'
            'dob' => ! empty($quote->dob) ? Carbon::parse($quote->dob)->format('Y-m-d') : '',
            'is_resident' => $emirate ? 'Yes' : 'No',
            'address' => $insuredKyc?->residential_address ?? ''
        ];
    }
    
    /**
     * Generates a unique UUID for the given document.
     *
     * @param  string  $documentType  The type of document.
     * @return string The generated UUID.
     */
    private function generateUniqueUuid()
    {
        do {
            $uuid = uniqid();
        } while (QuoteDocument::where('doc_uuid', $uuid)->exists());

        return $uuid;
    }

    /**
     * Logs into the SukoonMedex system and sets the session ID.
     *
     * @return void
     *
     * @throws Exception If login fails.
     */
    public function login()
    {
        try {
            $data = [
                'apiCall' => true,
                'username' => config('constants.SUKOON_USERNAME'),
                'password' => config('constants.SUKOON_PASSWORD'),
            ];
            $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
            $response = $this->request('/login/', 'post', $data, $headers)->json();

            if(empty($response['session_id'] ?? null))
                throw new Exception('Session id is missing');

            $this->sessionId = $response['session_id'] ?? null;

            return $response;

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Submits a form to the Democrance system.
     *
     * @param  array  $data  The data to submit with the form.
     * @param  mixed  $transaction  The transaction object.
     * @param  bool  $isInitial  Indicates if this is the initial form submission.
     * @return void
     *
     * @throws Exception If form submission fails.
     */
    public function submitPlan($data)
    {
        try {

            $result = $this->request('/policy/submit/'.$this->productSlug.'/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if(!empty(array_diff(['policy_number', 'policy_status'], array_keys($result))))
                throw new Exception('Not found (policy_number, policy_status)');

            // policy_status => SukoonPurchaseFlowEnum::STATUS_QUOTED
            return ['quote_policy' => $result['policy_number'], 'policy_status' => $result['policy_status']];

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function fetchErrors($result) {
        $this->errorMessages = $result['form']['error_msg'] ?? [];

        if(empty($this->errorMessages)) {
            $fields = collect($result['form']['fields'] ?? []);
            return $fields->filter(function($field) {
                return !empty($field['error_msg']) && array_push($this->errorMessages, [ $field['name'] => $field['error_msg'] ]);
            });
        }

        return [];
    }
    
    /**
     * Handles the quote policy logic for the given transaction and user details.
     *
     * @param  mixed  $transaction  The transaction object.
     * @param  array  $userDetail  The user details array.
     * @return void
     */
    private function submitPersonalDetail($data)
    {
        try {

            $result = $this->request('/policy/submit/'.$this->productSlug.'/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            $fields = $result['form']['fields'] ?? [];

            if(empty($fields) || !empty(array_diff(['policy_number', 'policy_status'], array_keys($result))))
                throw new Exception('Not found (fields, policy_number, policy_status)');

            $requiredFields = ['payment_plan', 'amount_disclaimer_text'];
            $pluckedFieldsValue = $this->pluckFieldsValue($fields, $requiredFields);

            // check required fields are present
            if(!empty(array_diff($requiredFields, array_keys($pluckedFieldsValue))))
                throw new Exception('Not found (payment_plan, amount_disclaimer_text)');

            return [
                'quote_policy' => $result['policy_number'], 
                'policy_status' => $result['policy_status'], // SukoonPurchaseFlowEnum::STATUS_NEW_POLICY
                'payment_plan' => $pluckedFieldsValue['payment_plan'], 
                'amount_disclaimer_text' => $pluckedFieldsValue['amount_disclaimer_text']
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function pluckFieldsValue($fields, $fieldsName, $isUpdate = false)
    {
        $pluckedProperties = [];

        foreach($fields as $field) {
            if(in_array($field['name'], $fieldsName)) {
                $pluckedProperties = [...$pluckedProperties, ...[$field['name'] => $field['value']]];
            }

            if($isUpdate) {
                match ($field['name']) {
                    'payment_plan' => $this->paymentPlan = $field['value'],
                    'amount_disclaimer_text' => $this->amountDisclaimerText = $field['value'],
                    default => null
                };
            }

            if(empty(array_diff($fieldsName, array_keys($pluckedProperties))))
                break;
        };
        return $pluckedProperties;
    }

    /**
     * Prepares the additional data array for the given quote.
     *
     * @param  mixed  $quote  The quote object.
     * @return array The prepared additional data.
     */
    private function prepareAdditionalData()
    {
        return [
            'form_name' => 'plan_picker',
            'plan_option' => $this->productSlug.'-personal_non_commercial_vehicles',
            "payment_plan" => $this->paymentPlan,
            "amount_disclaimer_text" => $this->amountDisclaimerText,
            'policy_number' => $this->quotePolicy
        ];
    }

    /**
     * Confirms the policy using the policy number.
     *
     * @return void
     */
    private function reviewSubmittedData()
    {
        try {

            $response = $this->request('/policy/'.$this->quotePolicy.'/confirm/', 
                'get', 
                ['confirm' => 'true'], 
                ['x-session-id' => $this->sessionId]
            );

            $responsePolicyData = $response['policy_data'] ?? [];
            if(!empty(array_diff(['quote_number', 'policy_status'], array_keys($responsePolicyData))))
                throw new Exception('Not found (quote_number, policy_status)');


            return [
                'quote_policy' => $responsePolicyData['quote_number'], 
                'policy_status' => $responsePolicyData['policy_status'] // SukoonPurchaseFlowEnum::STATUS_QUOTED
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Confirms the policy using the policy number.
     *
     * @return void
     */
    private function confirmSubmittedData()
    {
        try {
            $response = $this->request('/policy/'.$this->quotePolicy.'/confirm/', 
                'post', 
                ['confirm' => 'true'], 
                ['x-session-id' => $this->sessionId]
            );

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Initiates the payment process in the Democrance system.
     *
     * @return void
     *
     * @throws Exception If payment initiation fails.
     */
    public function initiatePaymentProcess()
    {
        $data = ['policy_number' => $this->quotePolicy, 'gateway' => $this->paymentGateway];

        try {
            $result = $this->request('/payment/initiate/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            return ['payment_token' => $result['token']];

        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Completes the payment process in the Democrance system.
     *
     * @return void
     *
     * @throws Exception If payment completion fails.
     */
    public function completeInvoicePayment()
    {
        $paymentReference = $this->transaction?->paymentCharges?->first()?->transaction_id ?? null;
        $data = ['payment_reference' => $paymentReference, 'payment_token' => $this->paymentToken];

        try {
            $result = $this->request('/payment/complete/'.$this->paymentGateway.'/?token='.$this->paymentToken, 'post', $data, [
                'x-session-id' => $this->sessionId,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if(empty($result['policy_number']))
                throw new Exception('Policy number is missing');

            return ['certificate_number' => $result['policy_number'], 'policy_status' => EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED];

        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getPolicyScheduleCoi()
    {
        try {
            $this->request('/policy/'.$this->certificateNumber.'/coi/', 'get', headers: ['x-session-id' => $this->sessionId]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCustomerTaxInvoice()
    {
        try {
            $this->request('/payment/'.$this->paymentToken.'/tax-invoice', 'get', headers: ['x-session-id' => $this->sessionId]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves documents related to the given quote and transaction.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $transaction  The transaction object.
     * @return void
     *
     * @throws Exception If document retrieval fails.
     */
    public function listGeneratedDocument()
    {
        try {
            $result = $this->request('/policy/'.$this->certificateNumber.'/generated-documents/', 'get', headers: ['x-session-id' => $this->sessionId]);
            
            return $result->json();

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getDocumentCreatedTimestamp($docName)
    {
        return Str::match('/-(\d{14})\.[^.]+$/', $docName);
    }

    public function syncGeneratedDocuments($documents, $quote, $embeddedTransaction)
    {
        try {
            $docStatus = ['created' => [], 'updated' => [], 'skipped' => []];

            foreach(($documents ?? []) as $document) {

                $docId = $document['doc_id'] ?? '';
                $docName = $document['name'] ?? '';

                $docNamePrefix = explode('-', $docName)[0];
                $docCode = match ($docNamePrefix) {
                    'TaxInvoice' => QuoteDocumentsEnum::CAR_TAX_INVOICE, // Tax Invoice (TaxInvoice)
                    'PolicyContract' => QuoteDocumentsEnum::POLICY_SCHEDULE, // Policy Schedule (PolicyContract)
                    'TaxInvoiceBuyer' => QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER, // Tax Invoice (TaxInvoiceBuyer)
                    default => null
                };

                if(empty($docCode)){
                    $docStatus['skipped'][] = $docName;
                    continue;
                }

                $document = $embeddedTransaction->documents()->where('document_type_code', $docCode)->first();
                if(!empty($document)) {
                    $existedDocTimestamp = $this->getDocumentCreatedTimestamp($document->doc_name);
                    $docCreatedTimestamp = $this->getDocumentCreatedTimestamp($docName);
                    if($existedDocTimestamp >= $docCreatedTimestamp) {
                        $docStatus['skipped'][] = $docName;
                        continue;
                    }
                }

                $downloadResult = $this->downloadDocument($quote, $embeddedTransaction, $docId, $docCode);
                if(!empty($downloadResult)) {
                    $createdOrUpdated = array_keys($downloadResult)[0];
                    array_push($docStatus[$createdOrUpdated], $downloadResult[$createdOrUpdated]);
                } else {
                    $docStatus['skipped'][] = $docName;
                }
            } 

            return $docStatus;

        } catch (Exception $e) {
            throw $e;
        }
    }


    /**
     * Download, upload & save the document
     *  - Download from sukoon-api
     *  - Upload document to azure
     *  - Save into database
     *
     * @param  mixed  $quote  The quote object
     * @param  mixed  $embeddedTransaction  The embedded transaction object
     * @param  string  $docId  The document ID
     * @param  string  $docCode  The document code
     * @return array|bool The result of the document save operation
     * @throws Exception If the document save operation fails
     */
    public function downloadDocument($quote, $embeddedTransaction, $docId, $docCode)
    {
        try {
            $documentType = DocumentType::where('code', $docCode)->where('quote_type_id', $this->quoteTypeId)->first();
            if(empty($documentType)) {
                $this->logFailure('DocumentType is missing', "DocumentType is not available for doc_code: {$docCode} & quote_type_id: {$this->quoteTypeId}", ['ref_id' => $quote->code]);
                return false;
            }

            $result = $this->request('/policy/download-document/'.$docId, 'get', headers: ['x-session-id' => $this->sessionId]);
            $content = $result->body();

            // Regular expression to match the specific response message with any policy number
            $pattern = '/Policy AP\d+ is not cancelled, cannot generate credit note/';

            // Handle the case where the content is empty or contains the specific response message
            if (empty($content) || preg_match($pattern, $content)) {
                $message = 'Document is not available on Sukoon';
                $this->logFailure($message.' doc_code: '.$docCode, $message, ['ref_id' => $quote->code]);
                return false;
            }

            $headers = $result->toPsrResponse()->getHeader('Content-Disposition');
            $filename = '';

            if (! empty($headers)) {
                preg_match('/filename="([^"]+)"/', $headers[0], $matches);
                if (isset($matches[1])) {
                    $filename = $matches[1];
                }
            }

            if ($filename != '') {
                $docUuid = $this->generateUniqueUuid();

                $dir = 'documents/'.$documentType->folder_path;
                $originalName = $filename;
                $docName = preg_replace('/\s+/', '', uniqid().'-'.$originalName);
                $uploadedDocument = $this->uploadDocument($docName, $content, $dir)?->getData();

                if(!($uploadedDocument->success ?? false)) {
                    $message = 'Document is not uploaded';
                    $this->logFailure($message.' doc_code: '.$docCode, $message, ['ref_id' => $quote->code]);
                    return false;
                }

                $document = $embeddedTransaction->documents()->where('document_type_code', $docCode)->first();
                $documentData = [
                    'original_name' => $originalName,
                    'doc_name' => $uploadedDocument->doc_name ?? null,
                    'doc_url' => $uploadedDocument->doc_url ?? null,
                    'doc_mime_type' => 'application/pdf',
                    'document_type_code' => $documentType->code,
                    'document_type_text' => $documentType->text,
                    'doc_uuid' => $docUuid,
                    'created_by_id' => null,
                ];

                $result = false;
                if (isset($document)) {
                    $document->update($documentData);
                    $result = ['updated' => $originalName];
                } else {
                    $embeddedTransaction->documents()->create($documentData);
                    $result = ['created' => $originalName];
                }

                return $result;
            } else {
                $message = 'Unable to determine filename from the response headers.';
                $this->logFailure($message.' doc_code : '.$docCode, $message, ['ref_id' => $quote->code, 'embeddedTransaction' => $embeddedTransaction]);
                return false;
            }
        } catch (Exception $e) {
            $this->logFailure('Get Document doc_code : '.$docCode, $e->getMessage(), ['ref_id' => $quote->code, 'embeddedTransaction' => $embeddedTransaction]);
            throw new Exception('downloadDocument ERROR: '. $e->getMessage());
        }
    }

    public function uploadDocument($docName, $content, $dir)
    {
        try {
            $fileNameAzure = uniqid()."_{$this->currentQuote->uuid}_$docName}";
            $docUrl = "{$dir}/{$fileNameAzure}";
            $filePathAzure = Storage::disk('azureIM')->put($docUrl, $content);
            
            if(!$filePathAzure) {
                throw new Exception('failed to upload document, doc_name: ' .$docName. ' doc_url: '. $docUrl);
            }

            return response()->json(['success' => $filePathAzure, 'doc_name' => $docName, 'doc_url' => $docUrl]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
