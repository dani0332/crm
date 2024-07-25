<?php

namespace App\Services;

use App\Enums\EmbeddedProductEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypeId;
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

    public function __construct()
    {
        $this->baseUrl = config('constants.SUKOON_DEMO_API_URL');
        $this->productSlug = config('constants.SUKOON_DEMO_PRODUCT_SLUG');
        $this->paymentGateway = config('constants.SUKOON_DEMO_PAYMENT_GATEWAY');
    }

    private function request($path, $method = 'post', $data = [], $headers = [])
    {
        $url = "{$this->baseUrl}/api/v" . config('constants.SUKOON_DEMO_API_VERSION') . $path;
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

    public function login()
    {
        $data = [
            'username' => config('constants.SUKOON_DEMO_USERNAME'),
            'password' => config('constants.SUKOON_DEMO_PASSWORD'),
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
            throw $e;
        }
    }

    public function formSubmit($data)
    {
        try {
            $result = $this->request('/policy/submit/' . $this->productSlug . '/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if (isset($result['policy_number']) && $result['policy_number'] && !$result['has_errors']) {
                return $this->policyNumber = $result['policy_number'];
            }

            throw new Exception('Form submit API failed or error in fields');
        } catch (Exception $e) {
            $this->logFailure('Form Submit', $e->getMessage(), $data);
            throw $e;
        }
    }

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
            throw $e;
        }
    }

    public function paymentComplete()
    {
        $data = ['payment_reference' => 'Payment reference here', 'payment_token' => $this->paymentToken];

        try {
            $result = $this->request('/payment/complete/' . $this->paymentGateway . '/?token=' . $this->paymentToken, 'post', $data, [
                'x-session-id' => $this->sessionId,
                'X-Requested-With' => 'XMLHttpRequest',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            if ($result) {
                return $this->documentPolicyNumber = $result['policy_number'];
            }

            throw new Exception('Payment complete API failed');
        } catch (Exception $e) {
            $this->logFailure('Payment Complete', $e->getMessage(), $data);
            throw $e;
        }
    }

    public function getCOIDocument($quote, $embeddedTransaction)
    {
        try {
            $result = $this->request('/policy/' . $this->documentPolicyNumber . '/coi/', 'get', [], ['x-session-id' => $this->sessionId]);
            $content = $result->body();
            $headers = $result->toPsrResponse()->getHeader('Content-Disposition');
            $filename = '';

            if (!empty($headers)) {
                preg_match('/filename="([^"]+)"/', $headers[0], $matches);
                if (isset($matches[1])) {
                    $filename = $matches[1];
                }
            }

            if ($filename) {
                $originalName = $filename;
                $docName = preg_replace('/\s+/', '', uniqid() . '_' . $originalName);
                $documentType = DocumentType::where('code', QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE)->where('quote_type_id', QuoteTypeId::Car)->first();
                $fileNameAzure = uniqid() . '_' . $quote->uuid . '_' . $docName;
                $docUrl = 'documents/' . $documentType->folder_path . '/' . $fileNameAzure;
                $filePathAzure = Storage::disk('azureIM')->put($docUrl, $content);

                $docUuid = $this->generateUniqueUuid();

                // TODO: check if document exist need to update that document file

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
            } else {
                throw new Exception('Unable to determine filename from the response headers.');
            }
        } catch (Exception $e) {
            $this->logFailure('Get COI Document', $e->getMessage(), ['quote' => $quote, 'embedded' => $embeddedTransaction]);
            throw $e;
        }
    }

    public function getTransactionDetails()
    {
        $data = ['template' => $this->invoiceBuyer];

        try {
            $result = $this->request('/policy/' . $this->documentPolicyNumber, 'post', $data, [
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
            throw $e;
        }
    }

    public function processDemocranceSubmission($quote, $ep, $transaction)
    {
        try {
            $this->currentQuote = $quote;
            

            if (!$ep) {
                throw new Exception('Embedded transaction not found');
            }

            if (!$this->validateCustomerDetail($quote->customer->emirates_id_number, $quote->customer->emirates_id_expiry_date)) {
                throw new Exception('Invalid Emirates ID or Expiry Date. Please check and try again.');
            }

            $shortCode = $ep->short_code;
            $userDetail = [
                'first_name' => $quote->first_name,
                'last_name' => $quote->last_name,
                'dob' => Carbon::parse($quote->dob)->format('Y-m-d'),
                'nationality' => 'AE',
                'is_resident' => $quote->emirate ? 'Yes' : 'No',
                'emirate' => $quote->emirate->text,
                'address' => 'Something, somewhere',
                'email' => 'hitesh.motwani@insurancemarket.ae',
                'mobile' => '+971505027325',
                'plan_option' => $this->productSlug . '_' . strtolower(EmbeddedProductEnum::$shortCode()->value),
            ];

            $this->login();
            $this->formSubmit($userDetail);

            $additionalData = [
                'form_name' => 'additional_details',
                'emirates_id_number' => '784-1000-0000000-0',
                'emirates_expiry_date' => '2024-09-30',
                // 'emirates_id_number' => $quote->customer->emirates_id_number,
                // 'emirates_expiry_date' => $quote->customer->emirates_id_expiry_date,
                'policy_number' => $this->policyNumber,
            ];

            $this->formSubmit($additionalData);
            $this->request('/policy/' . $this->policyNumber . '/confirm/', 'post', ['confirm' => 'true'], ['x-session-id' => $this->sessionId]);
            $this->paymentInitiate();
            $this->paymentComplete();
            $transactionDetail = $this->getTransactionDetails();
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
            $this->getCOIDocument($quote, $transaction);
        } catch (Exception $e) {
            $this->logFailure('Process Democrance Submission', $e->getMessage(), ['quote' => $quote]);
            throw $e;
        }
    }

    private function validateCustomerDetail($emiratesId, $emiratesIdExpiryDate)
    {
        $patternOfEID = '/^784-[0-9]{4}-[0-9]{7}-[0-9]{1}$/';

        return preg_match($patternOfEID, $emiratesId) && $emiratesIdExpiryDate >= Carbon::now();
    }

    private function logFailure($operation, $message, $data = [])
    {
        info('SUKOON DEMOCRANCE Service Failure', [
            'operation' => $operation,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function logRequest($status, $message, $data, $url = '', $response = '', $parentFunction = '')
    {
        // Truncate response if it's too large
        $maxTextLength = 65535; // The maximum length for MySQL TEXT type

        // Ensure response is a JSON string
        $response = is_array($response) ? json_encode($response) : $response;

        // Check if the response exceeds the maximum length
        if (strlen($response) > $maxTextLength) {
            // Save the large response to a file
            $responseFilePath = storage_path('logs/response_sukoon_democrance_' . uniqid() . '.json');
            file_put_contents($responseFilePath, $response);
            $response = 'Response too large, saved to: ' . $responseFilePath;
        }
        $logData = [
            'status' => $status,
            'request' => is_array($data) ? json_encode($data) : $data,
            'response' => $response,
            'execution_method' => $parentFunction,
            'quote_data' => json_encode($this->currentQuote),
            'quote_uuid' => $this->currentQuote->uuid,
            'provider_id' => InsuranceProvider::where('code', InsuranceProvidersEnum::OIC)->value('id'),
            'call_type' => 'product',
        ];
        InsurerRequestResponse::create($logData);

        info('SUKOON DEMOCRANCE Service Log', ['message' => $message, 'url' => $url, ...$logData]);
    }

    private function generateUniqueUuid()
    {
        do {
            $uuid = uniqid();
        } while (QuoteDocument::where('doc_uuid', $uuid)->exists());

        return $uuid;
    }
}
