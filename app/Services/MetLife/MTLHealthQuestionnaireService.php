<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Services\Logger\LoggerService;
use App\Enums\QuoteTypes;
use App\Enums\DocumentTypeCode;
use App\Models\PersonalQuote;
use App\Models\DocumentType;
use App\Services\QuoteDocumentService;
use Exception;

class MTLHealthQuestionnaireService
{
    public function syncHealthQuestionnaire(array $requestData)
    {
        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($requestData['quote_uuid']));
        
        LoggerService::info('DEBUG: Starting health questionnaire sync', [
            'quote_uuid' => $requestData['quote_uuid'],
            'policy_number' => $requestData['policy_number']
        ]);
        
        $quote = PersonalQuote::where('uuid', $requestData['quote_uuid'])->first();
        if (!$quote) {
            LoggerService::warning('DEBUG: Quote not found', ['quote_uuid' => $requestData['quote_uuid']]);
            throw new Exception('Quote not found for UID: ' . $requestData['quote_uuid']);
        }
        
        LoggerService::info('DEBUG: Quote found', ['quote_id' => $quote->id, 'quote_code' => $quote->code]);

        $healthQuestionnaire = $this->fetchHealthQuestionnaire($requestData['policy_number']);
        LoggerService::info('DEBUG: Health questionnaire fetched', [
            'form_name' => $healthQuestionnaire['form_name'] ?? 'Unknown',
            'fields_count' => count($healthQuestionnaire['fields'] ?? [])
        ]);
        
        $data = $this->prepareHealthQuestionnaireData($healthQuestionnaire, $requestData['quote_uuid']);
        LoggerService::info('DEBUG: Data prepared for PDF', ['pdf_filename' => $data['pdf_filename']]);

        $documentType = DocumentType::where('code', DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE)->first();
        if (!$documentType) {
            LoggerService::warning('DEBUG: Document type not found', ['code' => DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE]);
            throw new Exception('Document type not found for code: ' . DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE);
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

        if (!$document) {
            LoggerService::warning('DEBUG: Document upload failed', ['quote_uuid' => $quote->uuid]);
            throw new Exception('Failed to upload health questionnaire document for quote: ' . $quote->uuid);
        }

        LoggerService::info('DEBUG: Health questionnaire sync completed successfully', [
            'document_id' => $document->id ?? 'N/A',
            'quote_uuid' => $requestData['quote_uuid']
        ]);

        return $document;
    }

    private function fetchHealthQuestionnaire(string $policyNumber): array
    {
        LoggerService::info('DEBUG: Fetching health questionnaire from MetLife API', ['policy_number' => $policyNumber]);

        $metLifeService = app(MetLifeApiService::class);
        $endpoint = '/en/api/v' . $metLifeService->getApiVersion() . '/policy/' . $policyNumber . '/';
        LoggerService::info('DEBUG: Making API request', ['endpoint' => $endpoint]);
        
        $response = $metLifeService->makeRequest($endpoint, 'GET');
        LoggerService::info('DEBUG: API response received', [
            'success' => $response['success'] ?? false,
            'has_data' => isset($response['data']),
            'response_keys' => array_keys($response)
        ]);

        if (!$response['success']) {
            LoggerService::warning('DEBUG: API request failed', [
                'policy_number' => $policyNumber,
                'error' => $response['message'] ?? 'Unknown error'
            ]);
            throw new Exception('Failed to fetch health questionnaire: ' . ($response['message'] ?? 'Unknown error'));
        }

        $rawData = $response['data']['data'] ?? $response['data'] ?? $response;
        LoggerService::info('DEBUG: Raw API data structure', [
            'has_submitted_data' => isset($rawData['submitted_data']),
            'has_fields' => isset($rawData['submitted_data']['fields']),
            'fields_count' => count($rawData['submitted_data']['fields'] ?? [])
        ]);

        $healthQuestionnaire = $this->extractHealthQuestionnaire($rawData);

        if (empty($healthQuestionnaire)) {
            LoggerService::warning('DEBUG: Health questionnaire not found in response', [
                'policy_number' => $policyNumber,
                'available_forms' => array_column($rawData['submitted_data']['fields'] ?? [], 'form_name')
            ]);
            throw new Exception('Health questionnaire not found for policy: ' . $policyNumber);
        }

        LoggerService::info('DEBUG: Health questionnaire extracted successfully', [
            'policy_number' => $policyNumber,
            'form_name' => $healthQuestionnaire['form_name'] ?? 'Unknown',
            'fields_count' => count($healthQuestionnaire['fields'] ?? [])
        ]);

        return $healthQuestionnaire;
    }

    private function extractHealthQuestionnaire(array $responseData): ?array
    {
        $fields = $responseData['submitted_data']['fields'] ?? [];
        LoggerService::info('DEBUG: Extracting health questionnaire', [
            'total_fields' => count($fields),
            'field_names' => array_column($fields, 'form_name')
        ]);
        
        foreach ($fields as $index => $field) {
            LoggerService::info('DEBUG: Checking field', [
                'index' => $index,
                'form_name' => $field['form_name'] ?? 'N/A',
                'form_type' => $field['form_type'] ?? 'N/A',
                'is_health_questionnaire' => $this->isHealthQuestionnaire($field)
            ]);
            
            if ($this->isHealthQuestionnaire($field)) {
                LoggerService::info('DEBUG: Health questionnaire found', [
                    'form_name' => $field['form_name'],
                    'fields_count' => count($field['fields'] ?? [])
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
               $field['form_name'] === 'Health Questionnaire' &&
               $field['form_id'] === 'Health Questionnaire' &&
               $field['form_title'] === 'Health Questionnaire' &&
               $field['form_type'] === 'form';
    }

    private function prepareHealthQuestionnaireData(array $healthQuestionnaire, string $quoteUuid): array
    {
        return [
            'pdf_filename' => 'Health Questionnaire',
            'health_questionnaire' => $healthQuestionnaire,
            'quote_uuid' => $quoteUuid
        ];
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
}
