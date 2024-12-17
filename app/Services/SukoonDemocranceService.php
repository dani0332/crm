<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmbeddedProductEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use App\Models\DocumentType;
use App\Models\InsuranceProvider;
use App\Models\InsurerRequestResponse;
use App\Models\QuoteDocument;
use App\Repositories\EmbeddedProductRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SukoonDemocranceService
{
    private $baseUrl;
    private $sessionId;
    private $productSlug;
    private $paymentGateway;
    private $policyNumber;
    private $paymentToken;
    private $documentPolicyNumber;
    private $currentQuote;
    private $invoiceBuyer;
    private $documentTemplateIds;
    private $mappedDocumentTemplates = [];

    public function __construct()
    {
        $this->baseUrl = config('constants.SUKOON_API_URL');
        $this->productSlug = ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PRODUCT_SLUG)->value('value');
        $this->invoiceBuyer = config('constants.SUKOON_INVOICE_BUYER');
        $this->paymentGateway = ApplicationStorage::where('key_name', ApplicationStorageEnums::SUKOON_PAYMENT_GATEWAY)->value('value');
        $this->documentTemplateIds = ApplicationStorage::select('key_name', 'value')->whereIn('key_name', [
            ApplicationStorageEnums::SUKOON_TEMPLATE_POLICY_CERTIFICATE,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT_BUYER,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE,
            ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE_BUYER,
        ])->get();
        $this->mapDocumentsType();
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
        $client = Http::withHeaders($headers);

        // Get the call stack
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $parentFunction = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'Unknown';

        $response = $client->withBody(json_encode($data), 'application/json')->send($method, $url)->onError(function ($response) use ($data, $parentFunction, $url) {
            $this->logRequest('failed', 'Service Exception', $data, $url, json_encode($response), $parentFunction);
            $msg = $response->json()['msg'] ?? 'SUKOON DEMOCRANCE Service Exception';
            vAbort($msg);
        });

        $this->logRequest('passed', 'Request successful', $data, $url, $response->body(), $parentFunction);

        return $response;
    }

    /**
     * Logs into the Democrance system and sets the session ID.
     *
     * @return void
     *
     * @throws Exception If login fails.
     */
    public function login()
    {
        $data = [
            'username' => config('constants.SUKOON_USERNAME'),
            'password' => config('constants.SUKOON_PASSWORD'),
        ];
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        try {
            $result = $this->request('/login/', 'post', $data, $headers)->json();

            if (isset($result['session_id'])) {
                return $this->sessionId = $result['session_id'];
            }

            throw new Exception('Login failed');
        } catch (Exception $e) {
            $this->logFailure('Login', $e->getMessage(), $data);
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

            if (isset($result['policy_number']) && $result['policy_number'] && ! $result['has_errors']) {
                $save && $transaction->update(['quote_policy' => $result['policy_number']]);

                return $this->policyNumber = $result['policy_number'];
            }

            throw new Exception('Form submit API failed or error in fields');
        } catch (Exception $e) {
            $this->logFailure('Form Submit', $e->getMessage(), $data);
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
            $this->logFailure('Payment Initiate', $e->getMessage(), $data);
        }
    }

    /**
     * Completes the payment process in the Democrance system.
     *
     * @return void
     *
     * @throws Exception If payment completion fails.
     */
    public function paymentComplete($transaction)
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
                $transaction->update(['certificate_number' => $result['policy_number']]);

                return $this->documentPolicyNumber = $result['policy_number'];
            }

            throw new Exception('Payment complete API failed');
        } catch (Exception $e) {
            $this->logFailure('Payment Complete', $e->getMessage(), $data);
        }
    }

    /**
     * Retrieves a document for the given quote, embedded transaction, template ID, and document code.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $embeddedTransaction  The embedded transaction object.
     * @param  int  $templateId  The template ID.
     * @param  string  $docCode  The document code.
     * @return mixed The document.
     */
    public function getDocument($quote, $embeddedTransaction, $templateId, $docCode)
    {
        try {
            $data = ['template' => $templateId];
            $result = $this->request('/policy/'.$this->documentPolicyNumber.'/coi/', 'post', $data, ['x-session-id' => $this->sessionId]);
            $content = $result->body();

            // Regular expression to match the specific response message with any policy number
            $pattern = '/Policy AP\d+ is not cancelled, cannot generate credit note/';

            // Handle the case where the content is empty or contains the specific response message
            if (empty($content) || preg_match($pattern, $content)) {
                $message = 'Document is not available on democrance';
                $this->logFailure($message.' '.$templateId.' doc_code : '.$docCode, $message, ['quote' => $quote, 'embeddedTransaction' => $embeddedTransaction]);

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
                $originalName = $filename;
                $docName = preg_replace('/\s+/', '', uniqid().'_'.$originalName);
                $documentType = DocumentType::where('code', $docCode)->where('quote_type_id', QuoteTypeId::Car)->first();
                $fileNameAzure = uniqid().'_'.$quote->uuid.'_'.$docName;
                $docUrl = 'documents/'.$documentType->folder_path.'/'.$fileNameAzure;
                $filePathAzure = Storage::disk('azureIM')->put($docUrl, $content);
                $docUuid = $this->generateUniqueUuid();

                $document = $embeddedTransaction->documents()->where('document_type_code', $documentType->code)->first();
                if (isset($document)) {
                    $document->update([
                        'doc_name' => $docName,
                        'original_name' => $originalName,
                        'doc_url' => $docUrl,
                        'doc_mime_type' => 'application/pdf',
                        'document_type_code' => $documentType->code,
                        'document_type_text' => $documentType->text,
                        'doc_uuid' => $docUuid,
                        'created_by_id' => null,
                    ]);
                } else {
                    $documentData = [
                        'doc_name' => $docName,
                        'original_name' => $originalName,
                        'doc_url' => $docUrl,
                        'doc_mime_type' => 'application/pdf',
                        'document_type_code' => $documentType->code,
                        'document_type_text' => $documentType->text,
                        'doc_uuid' => $docUuid,
                        'created_by_id' => null,
                    ];
                    $embeddedTransaction->documents()->create($documentData);
                }
            } else {
                $message = 'Unable to determine filename from the response headers.';
                $this->logFailure($message.' '.$templateId.' doc_code : '.$docCode, $message, ['quote' => $quote, 'embeddedTransaction' => $embeddedTransaction]);
            }
        } catch (Exception $e) {
            $this->logFailure('Get Document template_id : '.$templateId.' doc_code : '.$docCode, $e->getMessage(), ['quote' => $quote, 'embeddedTransaction' => $embeddedTransaction]);
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
            info('Sukoon DemocranceDocuments mapped correctly documents :'.json_encode($this->mappedDocumentTemplates));
            foreach ($this->mappedDocumentTemplates as $index => $doc) {
                $this->getDocument($quote, $embeddedTransaction, $doc, $index);
            }
        } catch (Exception $e) {
            $this->logFailure('Get Documents', $e->getMessage(), ['quote' => $quote]);
        }
    }

    /**
     * Retrieves transaction details from the Democrance system.
     *
     * @return array The transaction details.
     *
     * @throws Exception If retrieval fails.
     */
    public function getTransactionDetails()
    {
        $data = ['template' => $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE]];

        try {
            $result = $this->request('/policy/'.$this->documentPolicyNumber, 'post', $data, [
                'x-session-id' => $this->sessionId,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if ($result) {
                return $result;
            }

            throw new Exception('Transaction Detail Api failed');
        } catch (Exception $e) {
            $this->logFailure('Transaction Detail Api Complete', $e->getMessage(), $data);
        }
    }

    /**
     * Processes the Democrance submission for the given quote and transaction.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $transaction  The transaction object.
     * @return void
     */
    public function processDemocranceSubmission($quote, $transaction)
    {
        try {
            $this->currentQuote = $quote;

            $this->validateCustomerDetails($quote);

            $userDetail = $this->prepareUserDetails($quote, $transaction);

            $this->login();

            $this->handleQuotePolicy($transaction, $userDetail);

            $additionalData = $this->prepareAdditionalData($quote);

            $this->formSubmit($additionalData);

            $this->confirmPolicy();

            $this->initiateAndCompletePayment($transaction);

            $this->getDocuments($quote, $transaction);

            $transactionDetail = $this->getTransactionDetails();

            $this->updateTransaction($transaction, $transactionDetail);

            EmbeddedProductRepository::sendDocument([
                'epId' => $transaction->product->embeddedProduct->id,
                'modelType' => quoteTypeCode::Car,
                'quoteId' => $quote->id,
            ]);
        } catch (Exception $e) {
            $this->logFailure('Process Democrance Submission', $e->getMessage(), ['quote' => $quote]);
        }
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
        if (! $this->validateCustomerDetail(
            $quote->customer->emirates_id_number,
            $quote->customer->emirates_id_expiry_date,
            $quote->customer->detail->residential_address
        )) {
            throw new Exception('Address cannot be empty, Invalid Emirates ID or Expiry Date. Please check and try again.');
        }
    }

    private function validateCustomerDetail($emiratesId, $emiratesIdExpiryDate, $address)
    {
        $patternOfEID = '/^784-[0-9]{4}-[0-9]{7}-[0-9]{1}$/';

        return preg_match($patternOfEID, $emiratesId) && $emiratesIdExpiryDate >= Carbon::now() && (! empty($address) || $address != '');
    }

    /**
     * Prepares the user details array for the given quote and transaction.
     *
     * @param  mixed  $quote  The quote object.
     * @param  mixed  $transaction  The transaction object.
     * @return array The prepared user details.
     */
    private function prepareUserDetails($quote, $transaction)
    {
        $shortCode = $transaction->product->embeddedProduct->short_code;

        if (! empty($quote->quoteRequestEntityMapping)) {
            $firstName = $quote->first_name ?? '';
            $lastName = $quote->last_name ?? '';
        } else {
            $firstName = $quote->customer->insured_first_name ?? '';
            $lastName = $quote->customer->insured_last_name ?? '';
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'dob' => ! empty($quote->dob) ? Carbon::parse($quote->dob)->format('Y-m-d') : '',
            'nationality' => 'AE',
            'is_resident' => $quote->emirate ? 'Yes' : 'No',
            'emirate' => $quote->emirate->text,
            'address' => $quote->customer->detail->residential_address ?? '',
            'email' => 'hitesh.motwani@insurancemarket.ae',
            'mobile' => '+971505027325',
            'plan_option' => $this->productSlug.'_'.strtolower(EmbeddedProductEnum::$shortCode()->value),
        ];
    }

    /**
     * Handles the quote policy logic for the given transaction and user details.
     *
     * @param  mixed  $transaction  The transaction object.
     * @param  array  $userDetail  The user details array.
     * @return void
     */
    private function handleQuotePolicy($transaction, $userDetail)
    {
        if ($transaction->quote_policy == null) {
            $this->formSubmit($userDetail, $transaction, true);
        } else {
            $this->policyNumber = $transaction->quote_policy;
        }
    }

    /**
     * Prepares the additional data array for the given quote.
     *
     * @param  mixed  $quote  The quote object.
     * @return array The prepared additional data.
     */
    private function prepareAdditionalData($quote)
    {
        return [
            'form_name' => 'additional_details',
            'emirates_id_number' => $quote->customer->emirates_id_number,
            'emirates_expiry_date' => $quote->customer->emirates_id_expiry_date,
            'policy_number' => $this->policyNumber,
        ];
    }

    /**
     * Confirms the policy using the policy number.
     *
     * @return void
     */
    private function confirmPolicy()
    {
        $this->request('/policy/'.$this->policyNumber.'/confirm/', 'post', ['confirm' => 'true'], ['x-session-id' => $this->sessionId]);
    }

    /**
     * Initiates and completes the payment for the given transaction.
     *
     * @param  mixed  $transaction  The transaction object.
     * @return void
     */
    private function initiateAndCompletePayment($transaction)
    {
        $this->paymentInitiate();

        if ($transaction->certificate_number == null) {
            $this->paymentComplete($transaction);
        } else {
            $this->documentPolicyNumber = $transaction->certificate_number;
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

        $transaction->update([
            'certificate_number' => $this->documentPolicyNumber,
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
     * Maps document types to the corresponding Democrance document types.
     *
     * @param  array  $documents  The documents to map.
     * @return array The mapped document types.
     */
    private function mapDocumentsType()
    {
        foreach ($this->documentTemplateIds as $template) {
            switch ($template->key_name) {
                case ApplicationStorageEnums::SUKOON_TEMPLATE_POLICY_CERTIFICATE:
                    $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE] = $template->value;
                    break;
                case ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT:
                    $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_CREDIT] = $template->value;
                    break;
                case ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_CREDIT_BUYER:
                    $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_CREDIT_RAISE_BY_BUYER] = $template->value;
                    break;
                case ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE:
                    $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_INVOICE] = $template->value;
                    break;
                case ApplicationStorageEnums::SUKOON_TEMPLATE_TAX_INVOICE_BUYER:
                    $this->mappedDocumentTemplates[QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER] = $template->value;
                    break;
            }
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
            $responseFilePath = storage_path('logs/response_sukoon_democrance_'.uniqid().'.json');
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
            'request' => is_array($data) ? json_encode($data) : $data,
            'response' => $response,
            'execution_method' => $parentFunction,
            'quote_data' => json_encode($this->currentQuote),
            'quote_uuid' => $this->currentQuote->uuid,
            'provider_id' => InsuranceProvider::where('code', InsuranceProvidersEnum::OIC)->value('id'),
            'call_type' => 'EmbeddedProduct',
        ];
        InsurerRequestResponse::create($logData);

        info('SUKOON DEMOCRANCE Service Log', ['message' => $message, 'url' => $url, ...$logData]);
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
        info('SUKOON DEMOCRANCE Service Failure', [
            'operation' => $operation,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
