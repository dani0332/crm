<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SukoonPurchaseFlowEnum;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\InsuranceProvider;
use App\Models\InsurerRequestResponse;
use App\Models\QuoteDocument;
use App\Repositories\EmbeddedProductRepository;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SukoonDriverMedexService
{
    /**
     * Create a new class instance.
     */
    private $sukoonRequestUrl;
    private $sessionId;
    private $currentQuote;
    private $quoteTypeId;
    private $productSlug;
    private $quoteNumber;
    private $policyNumber;
    private $policyStatus;
    private $paymentGateway;
    private $paymentToken;

    private $paymentPlan;
    private $amountDisclaimerText;
    
    private string $logPrefix = 'Sukoon Medex Service:';
    private array $errorMessages = [];
    private int $currentStep = 0;
    
    public function __construct(
        private $username = '',
        private $password = '',
    ) {
        $this->sukoonRequestUrl = config('constants.SUKOON_API_URL')."/api/v".config('constants.SUKOON_API_VERSION');
    }

    private function viewQuotePolicy($transaction)
    {
        try {
            $headers = ['x-session-id' => $this->sessionId, 'Content-Type' => 'application/json', 'Accept' => 'application/json'];
            $response = $this->request("/policy/{$transaction->certificate_number}", 'get', headers: $headers)->json();
            
            return $response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function initiatePurchaseFlow($transaction)
    {
        try {
            $this->username = config('constants.SUKOON_USERNAME');
            $this->password = config('constants.SUKOON_PASSWORD');
            $this->login();

            $this->quoteNumber = $transaction->quote_policy ?? null;
            $this->policyNumber = $transaction->certificate_number ?? null;
            $this->policyStatus = $transaction->policy_status ?? null;

            $this->productSlug = 'afia_driver_medex'; // ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PRODUCT_SLUG)->value('value'); TODO::
            $this->paymentGateway = ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PAYMENT_GATEWAY)->value('value');

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function syncSukoonData($transaction, $data)
    {
        $onlyFields = ['quote_policy', 'certificate_number', 'policy_status']; // TODO:: payment_plan, amount_disclaimer_text, payment_token
        $updateableData = collect($data)->only(...$onlyFields)->toArray();

        $transaction->update($updateableData);

        !empty($updateableData['quote_policy'] ?? null) && $this->quoteNumber = $updateableData['quote_policy'] ?? null;
        !empty($updateableData['certificate_number'] ?? null) && $this->policyNumber = $updateableData['certificate_number'] ?? null;
        !empty($updateableData['policy_status'] ?? null) && $this->policyStatus = $updateableData['policy_status'] ?? null;

        !empty($data['payment_plan'] ?? null) && $this->paymentPlan = $data['payment_plan'] ?? null;
        !empty($data['amount_disclaimer_text'] ?? null) && $this->amountDisclaimerText = $data['amount_disclaimer_text'] ?? null;
        !empty($data['payment_token'] ?? null) && $this->paymentToken = $data['payment_token'] ?? null;

        return $updateableData;
    }

    /**
     * Processes the SukoonDriverMedex Purchase Flow for the given quote and transaction.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $transaction  The transaction object.
     * @return void
     */
    public function processPurchaseFlow($quote, $quoteTypeId, $transaction)
    {
        try {
            $this->currentQuote = $quote;
            $this->quoteTypeId = $quoteTypeId;
    
            LoggerService::startQuoteLogging($this->currentQuote);

            if (! in_array($this->quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike]))
                throw new Exception('Only (Car / Bike) LOB are eligible');

            $this->validateCustomerDetails($quote);

            // STEP #1 init | Skiped

            // STEP #2 login
            $this->initiatePurchaseFlow($transaction);

            // STEP #3 getForm | Skiped

            // STEP #4 submitPersonalDetail
            $profileDetailResponse = $this->submitPersonalDetail($this->prepareUserDetails($quote));
            $this->syncSukoonData($transaction, $profileDetailResponse);

            // STEP #5 preReviewSubmittedData | Skiped

            // STEP #6 submitPlan
            $submitPlanResponse = $this->submitPlan($this->prepareAdditionalData());
            $this->syncSukoonData($transaction, $submitPlanResponse);


            // STEP #7 reviewSubmittedData
            $reviewSubmittedDataResponse = $this->reviewSubmittedData();
            $this->syncSukoonData($transaction, $reviewSubmittedDataResponse);

            // STEP #8 confirmSubmittedData
            $this->confirmSubmittedData();

            // STEP #9 listPaymentGateways | Skiped
            
            // STEP #10 initiatePaymentProcess
            $initPaymentResponse = $this->initiatePaymentProcess();
            $this->syncSukoonData($transaction, $initPaymentResponse);

            // STEP #11 completeInvoicePayment
            $invoicePaymentResponse = $this->completeInvoicePayment($transaction);
            $this->syncSukoonData($transaction, $invoicePaymentResponse);

            // STEP #12 getPolicyScheduleCoi 
            $this->getPolicyScheduleCoi();

            // STEP #13 getCustomerTaxInvoice
            $this->getCustomerTaxInvoice();

            // STEP #14 listGeneratedDocument
            $listGeneratedDocumentResponse = $this->listGeneratedDocument($quote, $transaction);

            // STEP #15 downloadDocument
            $this->saveGeneratedDocuments($listGeneratedDocumentResponse['documents'], $quote, $transaction);
            
            $quotePolicyResponse = $this->viewQuotePolicy($transaction);
            $this->updateTransaction($transaction, $quotePolicyResponse);

            // EmbeddedProductRepository::sendDocument([
            //     'epId' => $transaction->product->embeddedProduct->id,
            //     'modelType' => QuoteTypes::getName($this->quoteTypeId),
            //     'quoteId' => $quote->id,
            // ]);

        } catch (Exception $e) {
            $this->logFailure('Sukoon Purchase Flow Failed', $e->getMessage(), [
                'quote_uuid' => $quote->uuid ?? null,
                'error_messages' => $this->errorMessages
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
        $commission_amount = floatval($transactionDetail['payments'][0]['amount_breakdown']['commission_amount']) ? (float) $transactionDetail['payments'][0]['amount_breakdown']['commission_amount'] : (int) $transactionDetail['payments'][0]['amount_breakdown']['commission_amount'];
        $commissionVat = $commission_amount * 0.05 ?? 0;

        return $transaction->update([
            'certificate_number' => $this->policyNumber,
            'tax_invoice_no' => $transactionDetail['additional_data']['tax_invoice_document_number'] ?? null,
            'tax_invoice_buyer_no' => $transactionDetail['additional_data']['tax_invoice_buyer_document_number'] ?? null,
            'credit_note_no' => $transactionDetail['additional_data']['credit_note_document_number'] ?? null,
            'credit_note_buyer_no' => $transactionDetail['additional_data']['credit_note_buyer_document_number'] ?? null,
            'commission_with_vat' => $commission_amount + $commissionVat ?? null,
            'commission_without_vat' => $commission_amount,
            'policy_price' => $transactionDetail['payments'][0]['amount_breakdown']['policy_price'] ?? null,
            'policy_status' => $transactionDetail['payments'][0]['status'] ?? null,
        ]);
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
            $responseFilePath = storage_path('logs/response_sukoon_driver_medex_'.uniqid().'.json');
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
            'provider_id' => InsuranceProvider::where('code', InsuranceProvidersEnum::OIC)->value('id'), // TODO::
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

        $stepPrefix = $parentFunction == SukoonPurchaseFlowEnum::getName(SukoonPurchaseFlowEnum::GET_VIEW_QUOTE_POLICY) ? '' : "Step: #{$this->currentStep} ";
        LoggerService::info("{$this->logPrefix} API {$status} {$stepPrefix}{$parentFunction}", extra: $extraLog, context: ['message' => $message, 'endPoint' => $endPoint, ...$logData]);
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
    private function validateCustomerDetails($quote)
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
     * Logs into the SukoonDriverMedex system and sets the session ID.
     *
     * @return void
     *
     * @throws Exception If login fails.
     */
    public function login()
    {
        try {
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_LOGIN;

            $data = [
                'apiCall' => true,
                'username' => $this->username,
                'password' => $this->password,
            ];
            $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
            $response = $this->request('/login/', 'post', $data, $headers)->json();

            if(empty($response['session_id'] ?? null))
                throw new Exception('Session id is missing');

            $this->sessionId = $response['session_id'] ?? null;

            if($this->currentStep <= SukoonPurchaseFlowEnum::STEP_LOGIN)
                $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_LOGIN);

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
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_SUBMIT_PLAN;

            $result = $this->request('/policy/submit/'.$this->productSlug.'/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if(!empty(array_diff(['policy_number', 'policy_status'], array_keys($result))))
                throw new Exception('Not found (policy_number, policy_status)');

            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_SUBMIT_PLAN);

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
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_SUBMIT_PERSONAL_DETAIL;

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

            if(!empty(array_diff($requiredFields, array_keys($pluckedFieldsValue))))
                throw new Exception('Not found (payment_plan, amount_disclaimer_text)');

            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_SUBMIT_PERSONAL_DETAIL);

            return [
                'quote_policy' => $result['policy_number'], 
                'policy_status' => $result['policy_status'],
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
            'policy_number' => $this->quoteNumber
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
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_REVIEW_SUBMITED_DATA;

            $response = $this->request('/policy/'.$this->quoteNumber.'/confirm/', 
                'get', 
                ['confirm' => 'true'], 
                ['x-session-id' => $this->sessionId]
            );

            $responsePolicyData = $response['policy_data'] ?? [];
            if(!empty(array_diff(['quote_number', 'policy_status'], array_keys($responsePolicyData))))
                throw new Exception('Not found (quote_number, policy_status)');

            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_REVIEW_SUBMITED_DATA);

            return [
                'quote_policy' => $responsePolicyData['quote_number'], 
                'policy_status' => $responsePolicyData['policy_status']
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
            $response = $this->request('/policy/'.$this->quoteNumber.'/confirm/', 
                'post', 
                ['confirm' => 'true'], 
                ['x-session-id' => $this->sessionId]
            );

            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_CONFIRM_SUBMITED_DATA);
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
        $this->currentStep = SukoonPurchaseFlowEnum::STEP_INITIATE_PAYMENT_PROCESS;
        $data = ['policy_number' => $this->quoteNumber, 'gateway' => $this->paymentGateway];

        try {
            $result = $this->request('/payment/initiate/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_INITIATE_PAYMENT_PROCESS);
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
        $this->currentStep = SukoonPurchaseFlowEnum::STEP_COMPLETE_INVOICE_PAYMENT;
        $data = ['payment_reference' => 'Payment reference here', 'payment_token' => $this->paymentToken];

        try {
            $result = $this->request('/payment/complete/'.$this->paymentGateway.'/?token='.$this->paymentToken, 'post', $data, [
                'x-session-id' => $this->sessionId,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_COMPLETE_INVOICE_PAYMENT);
            return ['certificate_number' => $result['policy_number']];

        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getPolicyScheduleCoi()
    {
        try {
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_GET_POLICY_SCHEDULE_COI;
            $this->request('/policy/'.$this->policyNumber.'/coi/', 'get', headers: ['x-session-id' => $this->sessionId]);
            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_GET_POLICY_SCHEDULE_COI);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCustomerTaxInvoice()
    {
        try {
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_GET_CUSTOMER_TAX_INVOICE;
            $this->request('/payment/'.$this->paymentToken.'/tax-invoice', 'get', headers: ['x-session-id' => $this->sessionId]);
            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_GET_CUSTOMER_TAX_INVOICE);
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
    public function listGeneratedDocument($quote, $embeddedTransaction)
    {
        try {
            $this->currentStep = SukoonPurchaseFlowEnum::STEP_LIST_GENERATED_DOCUMENT;
            $result = $this->request('/policy/'.$this->policyNumber.'/generated-documents/', 'get', headers: ['x-session-id' => $this->sessionId]);
            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep(SukoonPurchaseFlowEnum::STEP_LIST_GENERATED_DOCUMENT);
            
            return $result->json();

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function saveGeneratedDocuments($documents, $quote, $embeddedTransaction)
    {
        try {
            $uploadedDocumentCount = 0;
            foreach(($documents ?? []) as $document) {

                $docId = $document['doc_id'] ?? '';
                $docName = $document['name'] ?? '';
                $fileCreated = DateTime::createFromFormat('d/m/Y H:i', $document['file_created'] ?? now('UTC')->format('d/m/Y H:i'));
                $fileCreatedTimestamp = $fileCreated->getTimestamp();

                $docNamePrefix = explode('-', $docName)[0];
                $docCode = match ($docNamePrefix) {
                    'TaxInvoice' => 'CTI', // Tax Invoice (TaxInvoice)
                    'PolicyContract' => 'CPS', // Policy Schedule (PolicyContract)
                    'TaxInvoiceBuyer' => 'CTIRBB', // Tax Invoice (TaxInvoiceBuyer)
                    default => null
                };

                if(empty($docCode)){
                    continue;
                }
                
                $document = $embeddedTransaction->documents()->where('document_type_code', $docCode)->first();
                if(!empty($document)) {
                    $docNameParts = explode('-', $document->doc_name);
                    $existedDocTimestamp = reset($docNameParts);
                    if($existedDocTimestamp >= $fileCreatedTimestamp) {
                        continue;
                    }
                }

                $this->downloadDocument($quote, $embeddedTransaction, $docId, $docCode, $fileCreatedTimestamp) && $uploadedDocumentCount++;
            }

            $generatedDocumentCounts = count($documents ?? []);
            LoggerService::info("Documents saved: {$uploadedDocumentCount} out of {$generatedDocumentCounts}");
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function downloadDocument($quote, $embeddedTransaction, $docId, $docCode, $fileCreatedTimestamp)
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
                $docName = preg_replace('/\s+/', '', $fileCreatedTimestamp.'-'.$filename);
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

                if (isset($document)) {
                    $document->update($documentData);
                } else {
                    $embeddedTransaction->documents()->create($documentData);
                }
                return true;
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

            LoggerService::info("{$this->logPrefix} upload document doc_name: {$docName}");
            return response()->json(['success' => $filePathAzure, 'doc_name' => $docName, 'doc_url' => $docUrl]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
