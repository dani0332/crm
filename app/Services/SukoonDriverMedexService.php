<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SukoonPurchaseFlowEnum;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\InsuranceProvider;
use App\Models\InsurerRequestResponse;
use App\Models\QuoteDocument;
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
    private $baseUrl;
    private $sessionId;
    private $currentQuote;
    private $quoteTypeId;
    private $productSlug;
    private $policyNumber;
    private $paymentGateway;
    private $paymentToken;
    private $documentPolicyNumber;

    private $paymentPlan;
    private $amountDisclaimerText;

    private $documentTemplateIds;
    private $mappedDocumentTemplates = [];
    
    
    public function __construct(
        private $logPrefix = 'Sukoon Medex Service:',
        private $errorMessages = [],
        private $currentStep = 0,
        private $stepName = SukoonPurchaseFlowEnum::STEP_INIT,
        
        private $documentTemplates = [
            ApplicationStorageEnums::SUKOON_TEMPLATE_POLICY_CERTIFICATE,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT_BUYER,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE_BUYER
        ],
    ) {}

    private function init()
    {
        $this->baseUrl = config('constants.SUKOON_API_URL');
        $this->productSlug = 'afia_driver_medex'; // ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PRODUCT_SLUG)->value('value');
        $this->paymentGateway = ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PAYMENT_GATEWAY)->value('value');
        $this->currentStep = SukoonPurchaseFlowEnum::getNextStep($this->currentStep);
        $this->stepName = SukoonPurchaseFlowEnum::getName($this->currentStep);

        $this->documentTemplateIds = ApplicationStorage::select('key_name', 'value')->whereIn('key_name', $this->documentTemplates)->get();
        $this->mapDocumentsType();
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
        $this->currentQuote = $quote;
        $this->quoteTypeId = $quoteTypeId;

        LoggerService::startQuoteLogging($this->currentQuote);

        try {

            if (! in_array($this->quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike])) {
                throw new Exception('Only (Car / Bike) LOB are eligible');
            }

            $this->validateCustomerDetails($quote);

            // STEP #1 init
            $this->init();

            // STEP #2 login
            $this->login();
            if(empty($this->sessionId))
                throw new Exception('Session id is missing');

            // STEP #3 getForm | Skiped

            // STEP #4 submitPersonalDetail
            $this->submitPersonalDetail($transaction, $this->prepareUserDetails($quote));
            if(empty($this->policyNumber) || empty($this->paymentPlan) || empty($this->amountDisclaimerText))
                throw new Exception('Policy No, Payment Plan or Amount Disclaimer Text is missing');

            // STEP #5 preReviewSubmittedData | Skiped

            // STEP #6 submitPlan
            $this->formSubmit($this->prepareAdditionalData());

            // STEP #7 reviewSubmittedData
            $this->confirmPolicy();

            // STEP #8 confirmSubmittedData
            $this->confirmPolicy(postMethod: true);

            // STEP #9 listPaymentGateways | Skiped

            // STEP (#10 initiatePaymentProcess) & (#11 completeInvoicePayment)
            $this->initiateAndCompletePayment($transaction);
            if(empty($this->documentPolicyNumber))
                throw new Exception('Document Policy Number is missing');

            // STEP #12 getPolicyScheduleCoi 
            $this->getPolicyScheduleCoi();

            // STEP #13 getCustomerTaxInvoice
            $this->getCustomerTaxInvoice();

            // STEP #14 listGeneratedDocument & #15 downloadDocument
            $this->getDocuments($quote, $transaction);

        } catch (Exception $e) {
            $this->logFailure('Sukoon Purchase Flow Failed', $e->getMessage(), [
                'quote_uuid' => $quote->uuid ?? null
            ]);
        }
    }

    /**
     * Makes an HTTP request to the specified path with the given method, data, and headers.
     *
     * @param  string  $path  The API endpoint path.
     * @param  string  $method  The HTTP method (default is 'post').
     * @param  array  $data  The data to send with the request.
     * @param  array  $headers  The headers to include with the request.
     * @return mixed The response from the API.
     */
    private function request($path, $method = 'post', $data = [], $headers = [])
    {
        $url = "{$this->baseUrl}/api/v".config('constants.SUKOON_API_VERSION').$path;

        // $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $parentFunction = $this->stepName; // isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'Unknown';
        $responseData = null;

        try {
            $client = Http::withHeaders($headers);

            $response = $client->withBody(json_encode($data), 'application/json')->send($method, $url)->onError(function ($response) {
                $msg = $response->json()['msg'] ?? "{$this->logPrefix} API Request Exception";
                $this->fetchErrors($response);

                $responseData = $response->json();
                $responseData['errorMessages'] = $this->errorMessages;
                throw new Exception($msg);
            });

            $contentType = $response->header('Content-Type');

            if (!str_contains($contentType, 'application/json')) {
                $this->logRequest('passed', 'Request Successful', $data, $url, parentFunction: $parentFunction);
                $this->currentStep = SukoonPurchaseFlowEnum::getNextStep($this->currentStep);
                $this->stepName = SukoonPurchaseFlowEnum::getName($this->currentStep);
                return $response;
            }

            $responseData = $response->json();

            if($responseData['has_errors'] ?? null) {
                $this->fetchErrors($response);
                $responseData['errorMessages'] = $this->errorMessages;
                $this->logRequest('failed', 'Request Error', $data, $url, $responseData, $parentFunction);
                return $response;
            }

            $this->logRequest('passed', 'Request Successful', $data, $url, $responseData, $parentFunction);
            $this->currentStep = SukoonPurchaseFlowEnum::getNextStep($this->currentStep);
            $this->stepName = SukoonPurchaseFlowEnum::getName($this->currentStep);
            return $response;

        } catch (Exception $e) {
            $this->logRequest('failed', $e->getMessage(), $data, $url, $responseData, $parentFunction);
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
    private function logRequest($status, $message, $data, $url = '', $response = '', $parentFunction = '')
    {
        // Truncate response if it's too large
        $maxTextLength = 65535; // The maximum length for MySQL TEXT type

        // Ensure response is a JSON string
        $response = is_array($response) ? json_encode($response) : $response;

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
            'provider_id' => InsuranceProvider::where('code', InsuranceProvidersEnum::OIC)->value('id'),
            'call_type' => 'EmbeddedProduct'
        ];
        $requestCallData = [
            'request' => is_array($data) ? json_encode($data) : $data,
            'response' => $response,
            'quote_data' => json_encode($this->currentQuote)
        ];

        InsurerRequestResponse::create([...$logData, ...$requestCallData]);
        $responseData = collect($response)->only('success', 'status', 'has_errors', 'policy_number', 'policy_status');
        $logData = [...$logData, ...$responseData];

        LoggerService::info("{$this->logPrefix} API {$status} Step: #{$this->currentStep} {$this->stepName}", context: ['message' => $message, 'url' => $url, ...$logData, ...$responseData]);
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
        $lastInsuredData = $quote->lastInsured;
        $insuredKyc = $lastInsuredData?->insuredKyc;
        $customerType = $lastInsuredData?->customer_type;
        $idType = $insuredKyc?->id_type;

        if (($customerType != CustomerTypeEnum::Individual || $idType != 'emiratesId') && ! $this->validateCustomerKycDetail(
            $insuredKyc?->id_number,
            $insuredKyc?->id_expiry_date,
            $insuredKyc?->residential_address
        )) {
            throw new Exception('Address cannot be empty, Invalid Emirates ID or Expiry Date. Please check and try again.');
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
        $lastInsuredData = $quote->lastInsured;
        $insuredKyc = $lastInsuredData?->insuredKyc;

        if (! empty($quote->quoteRequestEntityMapping)) {
            $firstName = $quote->first_name ?? '';
            $lastName = $quote->last_name ?? '';
        } else {
            $firstName = ($insuredKyc?->first_name ?? $quote->customer?->insured_first_name) ?? '';
            $lastName = ($insuredKyc?->last_name ?? $quote->customer?->insured_last_name) ?? '';
        }

        return [
            'form_name' => 'personal_details',
            "title" => $lastInsuredData->gender == 'Male' ? "Mr" : 'Ms',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'mobile' => '+971505027325',
            'email' => 'hitesh.motwani@insurancemarket.ae',
            'nationality' => 'AE',
            'emirate' => $quote->emirate->text ?? '',
            'emirates_id_number' => $insuredKyc?->id_type == 'emiratesId' ? $insuredKyc?->id_number : '', //'784-1989-8057715-1'
            'dob' => ! empty($quote->dob) ? Carbon::parse($quote->dob)->format('Y-m-d') : '',
            'is_resident' => $quote->emirate ? 'Yes' : 'No',
            'address' => $insuredKyc?->residential_address ?? ''
        ];
    }

    /**
     * Maps document types to the corresponding Democrance document types.
     *
     * @param  array  $documents  The documents to map.
     * @return array The mapped document types.
     */
    private function mapDocumentsType()
    {
        foreach ($this->documentTemplateIds as $template) {
            match ($template->key_name) {
                ApplicationStorageEnums::SUKOON_TEMPLATE_POLICY_CERTIFICATE => $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE] = $template->value,
                ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT => $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_CREDIT] = $template->value,
                ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT_BUYER => $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_CREDIT_RAISE_BY_BUYER] = $template->value,
                ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE => $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_INVOICE] = $template->value,
                ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE_BUYER => $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER] = $template->value,
                default => null
            };
        }
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
    public function login(): bool
    {
        try {
            $data = [
                'apiCall' => true,
                'username' => config('constants.SUKOON_USERNAME'),
                'password' => config('constants.SUKOON_PASSWORD'),
            ];
            $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
            $result = $this->request('/login/', 'post', $data, $headers)->json();

            if (!isset($result['session_id'])) {
                return false;
            }

            $this->sessionId = $result['session_id'];
            return true;

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
    public function formSubmit($data, $transaction = null, $save = false)
    {
        try {
            $result = $this->request('/policy/submit/'.$this->productSlug.'/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if(!empty($this->errorMessages)) {
                throw new Exception('Abort');
            }

            if (! empty($result['policy_number'] ?? null)) {
                $this->policyNumber = $result['policy_number'];
                ($save && $transaction) && $transaction->update(['quote_policy' => $this->policyNumber]);
            }

            return $result;

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
    private function submitPersonalDetail($transaction, $userDetail)
    {
        try {
            if ($transaction->quote_policy == null) {
                $response = $this->formSubmit($userDetail, $transaction, false); // TODO:: true

                $fields = collect($response['form']['fields'] ?? []);

                if(empty($fields))
                    return false;

                $fields->filter(function($field) {
                    match ($field['name']) {
                        'payment_plan' => $this->paymentPlan = $field['value'],
                        'amount_disclaimer_text' => $this->amountDisclaimerText = $field['value'],
                        default => null
                    };
                });
            } else {
                $this->policyNumber = $transaction->quote_policy;
            }

            return true;

        } catch (Exception $e) {
            throw $e;
        }
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
            'policy_number' => $this->policyNumber
        ];
    }

    /**
     * Confirms the policy using the policy number.
     *
     * @return void
     */
    private function confirmPolicy($postMethod = false)
    {
        try {
            $response = $this->request('/policy/'.$this->policyNumber.'/confirm/', 
                ($postMethod ? 'post' : 'get'), 
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
    public function paymentInitiate()
    {
        $data = ['policy_number' => $this->policyNumber, 'gateway' => $this->paymentGateway];

        try {
            $result = $this->request('/payment/initiate/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if ($result) {
                return $this->paymentToken = $result['token'];
            }

            throw new Exception('Payment initiate API failed');
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
    public function completeInvoicePayment($transaction)
    {
        $data = ['payment_reference' => 'Payment reference here', 'payment_token' => $this->paymentToken];

        try {
            $result = $this->request('/payment/complete/'.$this->paymentGateway.'/?token='.$this->paymentToken, 'post', $data, [
                'x-session-id' => $this->sessionId,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if ($result) {
                // $transaction->update(['certificate_number' => $result['policy_number']]); // TODO:: uncomment
                return $this->documentPolicyNumber = $result['policy_number'];
            }

            throw new Exception('Payment complete API failed');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Initiates and completes the payment for the given transaction.
     *
     * @param  mixed  $transaction  The transaction object.
     * @return void
     */
    private function initiateAndCompletePayment($transaction)
    {
        try {
            if ($transaction->certificate_number == null || true) { // TODO:: remove || true check

                // STEP #10 initiatePaymentProcess
                $this->paymentInitiate();

                // STEP #11 completeInvoicePayment
                $this->completeInvoicePayment($transaction);
            } else {
                $this->documentPolicyNumber = $transaction->certificate_number;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }


    public function getPolicyScheduleCoi()
    {
        try {
            $result = $this->request('/policy/'.$this->documentPolicyNumber.'/coi/', 'get', headers: ['x-session-id' => $this->sessionId]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getCustomerTaxInvoice()
    {
        try {
            $result = $this->request('/payment/'.$this->paymentToken.'/tax-invoice', 'get', headers: ['x-session-id' => $this->sessionId]);
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
    public function getDocuments($quote, $embeddedTransaction)
    {
        try {
            $result = $this->request('/policy/'.$this->documentPolicyNumber.'/generated-documents/', 'get', headers: ['x-session-id' => $this->sessionId]);
            $response = $result->json();

            foreach(($response['documents'] ?? []) as $document) {
                
                if(empty($document)) {
                    continue;
                }

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

                $this->downloadDocument($quote, $embeddedTransaction, $docId, $docCode, $fileCreatedTimestamp);
            }

            $generatedDocumentCounts = count($response['documents'] ?? []);
            LoggerService::info("Downloaded documents count: {$generatedDocumentCounts}");
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
            } else {
                $message = 'Unable to determine filename from the response headers.';
                $this->logFailure($message.' doc_code : '.$docCode, $message, ['ref_id' => $quote->code, 'embeddedTransaction' => $embeddedTransaction]);
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
