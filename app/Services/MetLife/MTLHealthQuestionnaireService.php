<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\DocumentNotificationEvent;
use App\Exceptions\MetLife\MetLifeException;
use App\Models\DocumentType;
use App\Models\PersonalQuote;
use App\Models\QuoteStatusLog;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;

class MTLHealthQuestionnaireService
{
    private const HEALTH_QUESTIONNAIRE_FORM_NAME = 'Health Questionnaire';

    public function syncHealthQuestionnaire(array $requestData)
    {
        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($requestData['quote_uuid']));

        LoggerService::info('DEBUG: Starting health questionnaire sync', [
            'quote_uuid' => $requestData['quote_uuid'],
            'policy_number' => $requestData['policy_number'],
        ]);

        $quote = PersonalQuote::with('customer')->where('uuid', $requestData['quote_uuid'])->first();
        if (! $quote) {
            LoggerService::warning('DEBUG: Quote not found', ['quote_uuid' => $requestData['quote_uuid']]);
            throw new MetLifeException(
                'Quote not found for UID: '.$requestData['quote_uuid'],
                MetLifeException::QUOTE_NOT_FOUND,
                ['quote_uuid' => $requestData['quote_uuid']]
            );
        }

        LoggerService::info('DEBUG: Quote found', ['quote_id' => $quote->id, 'quote_code' => $quote->code]);

        $healthQuestionnaire = $this->fetchHealthQuestionnaire($requestData['policy_number']);
        LoggerService::info('DEBUG: Health questionnaire fetched', [
            'form_name' => $healthQuestionnaire['form_name'] ?? 'Unknown',
            'fields_count' => count($healthQuestionnaire['fields'] ?? []),
        ]);

        $data = $this->prepareHealthQuestionnaireData($healthQuestionnaire, $requestData['quote_uuid'], $quote);
        LoggerService::info('DEBUG: Data prepared for PDF', ['pdf_filename' => $data['pdf_filename']]);

        $documentType = DocumentType::where('code', DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE)->first();
        if (! $documentType) {
            LoggerService::warning('DEBUG: Document type not found', ['code' => DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE]);
            throw new MetLifeException(
                'Document type not found for code: '.DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE,
                MetLifeException::DOCUMENT_TYPE_NOT_FOUND,
                ['document_type_code' => DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE]
            );
        }
        $data['document_type_code'] = $documentType->code;
        LoggerService::info('DEBUG: Document type found', ['document_type_id' => $documentType->id]);

        $pdfFile = $this->generateHealthQuestionnairePdf($data);
        LoggerService::info('DEBUG: PDF generated', ['pdf_size' => strlen($pdfFile)]);

        $document = app(QuoteDocumentService::class)->uploadQuoteDocument(
            $pdfFile,
            $data,
            $quote,
            false,
            false,
            false,
            true
        );

        if (! $document) {
            LoggerService::warning('DEBUG: Document upload failed', ['quote_uuid' => $quote->uuid]);
            throw new MetLifeException(
                'Failed to upload health questionnaire document for quote: '.$quote->uuid,
                MetLifeException::DOCUMENT_UPLOAD_FAILED,
                ['quote_uuid' => $quote->uuid, 'pdf_filename' => $data['pdf_filename'] ?? null]
            );
        }

        $this->updateQuoteStatusToApplicationPending($quote);

        event(new DocumentNotificationEvent([
            'quoteUID' => $quote->uuid,
            'status' => 'success',
        ]));

        LoggerService::info('DEBUG: Health questionnaire sync completed successfully', [
            'document_id' => $document->id ?? 'N/A',
            'quote_uuid' => $requestData['quote_uuid'],
        ]);

        return $document;
    }

    private function fetchHealthQuestionnaire(string $policyNumber): array
    {
        LoggerService::info('DEBUG: Fetching health questionnaire from MetLife API', ['policy_number' => $policyNumber]);

        $metLifeService = app(MetLifeApiService::class);
        $endpoint = '/en/api/v'.$metLifeService->getApiVersion().'/policy/'.$policyNumber.'/';
        LoggerService::info('DEBUG: Making API request', ['endpoint' => $endpoint]);

        $response = $metLifeService->makeRequest($endpoint, 'GET');
        LoggerService::info('DEBUG: API response received', [
            'success' => $response['success'] ?? false,
            'has_data' => isset($response['data']),
            'response_keys' => array_keys($response),
        ]);

        if (! $response['success']) {
            LoggerService::warning('DEBUG: API request failed', [
                'policy_number' => $policyNumber,
                'error' => $response['message'] ?? 'Unknown error',
            ]);
            throw new MetLifeException(
                'Failed to fetch health questionnaire: '.($response['message'] ?? 'Unknown error'),
                MetLifeException::FETCH_FAILED,
                ['policy_number' => $policyNumber, 'api_response' => $response]
            );
        }

        $rawData = $response['data']['data'] ?? $response['data'] ?? $response;
        LoggerService::info('DEBUG: Raw API data structure', [
            'has_submitted_data' => isset($rawData['submitted_data']),
            'has_fields' => isset($rawData['submitted_data']['fields']),
            'fields_count' => count($rawData['submitted_data']['fields'] ?? []),
        ]);

        $healthQuestionnaire = $this->extractHealthQuestionnaire($rawData);

        if (empty($healthQuestionnaire)) {
            $availableForms = array_column($rawData['submitted_data']['fields'] ?? [], 'form_name');
            LoggerService::warning('DEBUG: Health questionnaire not found in response', [
                'policy_number' => $policyNumber,
                'available_forms' => $availableForms,
            ]);
            throw new MetLifeException(
                'Health questionnaire not found for policy: '.$policyNumber,
                MetLifeException::QUESTIONNAIRE_NOT_FOUND,
                ['policy_number' => $policyNumber, 'available_forms' => $availableForms]
            );
        }

        LoggerService::info('DEBUG: Health questionnaire extracted successfully', [
            'policy_number' => $policyNumber,
            'form_name' => $healthQuestionnaire['form_name'] ?? 'Unknown',
            'fields_count' => count($healthQuestionnaire['fields'] ?? []),
        ]);

        return $healthQuestionnaire;
    }

    private function extractHealthQuestionnaire(array $responseData): ?array
    {
        $fields = $responseData['submitted_data']['fields'] ?? [];
        LoggerService::info('DEBUG: Extracting health questionnaire', [
            'total_fields' => count($fields),
            'field_names' => array_column($fields, 'form_name'),
        ]);

        foreach ($fields as $index => $field) {
            LoggerService::info('DEBUG: Checking field', [
                'index' => $index,
                'form_name' => $field['form_name'] ?? 'N/A',
                'form_type' => $field['form_type'] ?? 'N/A',
                'is_health_questionnaire' => $this->isHealthQuestionnaire($field),
            ]);

            if ($this->isHealthQuestionnaire($field)) {
                LoggerService::info('DEBUG: Health questionnaire found', [
                    'form_name' => $field['form_name'],
                    'fields_count' => count($field['fields'] ?? []),
                ]);

                return $field;
            }
        }

        LoggerService::warning('DEBUG: No health questionnaire found in fields');

        return null;
    }

    private function isHealthQuestionnaire(array $field): bool
    {
        return isset($field['form_name'], $field['form_id'], $field['form_title'], $field['form_type'], $field['fields']) &&
               $field['form_name'] === self::HEALTH_QUESTIONNAIRE_FORM_NAME &&
               $field['form_id'] === self::HEALTH_QUESTIONNAIRE_FORM_NAME &&
               $field['form_title'] === self::HEALTH_QUESTIONNAIRE_FORM_NAME &&
               $field['form_type'] === 'form';
    }

    private function prepareHealthQuestionnaireData(array $healthQuestionnaire, string $quoteUuid, PersonalQuote $quote): array
    {
        $pdfFilename = $this->generatePdfFilename($quote);

        return [
            'pdf_filename' => $pdfFilename,
            'health_questionnaire' => $healthQuestionnaire,
            'quote_uuid' => $quoteUuid,
        ];
    }

    private function generatePdfFilename(PersonalQuote $quote): string
    {
        $insurerName = 'Metlife';
        $refId = $quote->code;

        // Get customer name
        $customerName = $this->getCustomerName($quote);

        // Build filename without .pdf extension (QuoteDocumentService will add it)
        if ($customerName) {
            return "{$insurerName} Health Questionnaire for {$customerName} {$refId}";
        } else {
            return "{$insurerName} Health Questionnaire {$refId}";
        }
    }

    private function getCustomerName(PersonalQuote $quote): ?string
    {
        if (! $quote->customer) {
            LoggerService::warning('DEBUG: Customer not found for quote', [
                'quote_uuid' => $quote->uuid,
                'quote_code' => $quote->code,
            ]);

            return null;
        }

        $firstName = trim($quote->customer->first_name ?? '');
        $lastName = trim($quote->customer->last_name ?? '');

        if (empty($firstName) && empty($lastName)) {
            LoggerService::warning('DEBUG: Customer name is empty', [
                'quote_uuid' => $quote->uuid,
                'quote_code' => $quote->code,
                'customer_id' => $quote->customer->id,
            ]);

            return null;
        }

        return trim($firstName.' '.$lastName);
    }

    private function generateHealthQuestionnairePdf(array $data): string
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
        });

        return app('dompdf.wrapper')->loadView('pdf.life.health-questionnaire.health-questionnaire', compact('data'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'defaultTimeout' => 60,
            ])
            ->setPaper('A4')
            ->output();
    }

    private function updateQuoteStatusToApplicationPending(PersonalQuote $quote): void
    {
        if ($quote->quote_status_id === QuoteStatusEnum::ApplicationPending) {
            LoggerService::info('Quote status is already Application Pending', [
                'quote_uuid' => $quote->uuid,
                'quote_code' => $quote->code,
            ]);

            return;
        }

        $previousStatusId = $quote->quote_status_id;

        $quote->update([
            'quote_status_id' => QuoteStatusEnum::ApplicationPending,
            'quote_status_date' => now(),
        ]);

        QuoteStatusLog::create([
            'quote_type_id' => $quote->quote_type_id,
            'quote_request_id' => $quote->id,
            'current_quote_status_id' => QuoteStatusEnum::ApplicationPending,
            'previous_quote_status_id' => $previousStatusId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        LoggerService::info('Quote status updated to Application Pending after health questionnaire upload', [
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => QuoteStatusEnum::ApplicationPending,
        ]);
    }
}
