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

    public function formSubmit($data)
    {
        try {
            $result = $this->request('/policy/submit/'.$this->productSlug.'/', 'post', $data, [
                'x-session-id' => $this->sessionId,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->json();

            // TODO: has_error need to be checked for failure
            if (isset($result['policy_number']) && $result['policy_number'] && ! $result['has_errors']) {
                return $this->policyNumber = $result['policy_number'];
            }

            throw new Exception('Form submit API failed or error in fields');
        } catch (Exception $e) {
            $this->logFailure('Form Submit', $e->getMessage(), $data);
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
        }
    }

    public function paymentComplete()
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
                return $this->documentPolicyNumber = $result['policy_number'];
            }

            throw new Exception('Payment complete API failed');
        } catch (Exception $e) {
            $this->logFailure('Payment Complete', $e->getMessage(), $data);
        }
    }

    public function getDocument($quote, $embeddedTransaction, $templateId, $docCode)
    {
        try {
            $data = ['template' => $templateId];
            $result = $this->request('/policy/'.$this->documentPolicyNumber.'/coi/', 'post', $data, ['x-session-id' => $this->sessionId]);
            $content = $result->body();
            $headers = $result->toPsrResponse()->getHeader('Content-Disposition');
            $filename = '';

            if (! empty($headers)) {
                preg_match('/filename="([^"]+)"/', $headers[0], $matches);
                if (isset($matches[1])) {
                    $filename = $matches[1];
                }
            }

            if ($filename) {
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
                throw new Exception('Unable to determine filename from the response headers.');
            }
        } catch (Exception $e) {
            $this->logFailure('Get Document template_id : '.$templateId.' doc_code : '.$docCode, $e->getMessage(), ['quote' => $quote, 'embeddedTransaction' => $embeddedTransaction]);
        }
    }

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

    public function processDemocranceSubmission($quote, $transaction)
    {
        try {
            $this->currentQuote = $quote;

            if (! $this->validateCustomerDetail($quote->customer->emirates_id_number, $quote->customer->emirates_id_expiry_date)) {
                throw new Exception('Invalid Emirates ID or Expiry Date. Please check and try again.');
            }

            $shortCode = $transaction->product->embeddedProduct->short_code;

            $userDetail = [
                'first_name' => $quote->first_name,
                'last_name' => $quote->last_name,
                'dob' => ! empty($quote->customer->dob) ? Carbon::parse($quote->customer->dob)->format('Y-m-d') : '',
                'nationality' => 'AE',
                'is_resident' => $quote->emirate ? 'Yes' : 'No',
                'emirate' => $quote->emirate->text,
                'address' => $quote->customer->detail->residential_address ?? '',
                'email' => 'hitesh.motwani@insurancemarket.ae',
                'mobile' => '+971505027325',
                'plan_option' => $this->productSlug.'_'.strtolower(EmbeddedProductEnum::$shortCode()->value),
            ];

            $this->login();
            $this->formSubmit($userDetail);

            $additionalData = [
                'form_name' => 'additional_details',
                'emirates_id_number' => $quote->customer->emirates_id_number,
                'emirates_expiry_date' => $quote->customer->emirates_id_expiry_date,
                'policy_number' => $this->policyNumber,
            ];

            $this->formSubmit($additionalData);
            $this->request('/policy/'.$this->policyNumber.'/confirm/', 'post', ['confirm' => 'true'], ['x-session-id' => $this->sessionId]);
            $this->paymentInitiate();
            $this->paymentComplete();
            $this->getDocuments($quote, $transaction);
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
            EmbeddedProductRepository::sendDocument(['epId' => $transaction->product->embeddedProduct->id, 'modelType' => quoteTypeCode::Car, 'quoteId' => $quote->id]);
        } catch (Exception $e) {
            $this->logFailure('Process Democrance Submission', $e->getMessage(), ['quote' => $quote]);
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

    private function generateUniqueUuid()
    {
        do {
            $uuid = uniqid();
        } while (QuoteDocument::where('doc_uuid', $uuid)->exists());

        return $uuid;
    }
}
