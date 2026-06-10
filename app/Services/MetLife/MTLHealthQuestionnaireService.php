<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\DocumentNotificationEvent;
use App\Exceptions\MetLife\MetLifeException;
use App\Models\DocumentType;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;

class MTLHealthQuestionnaireService
{
    private const HEALTH_QUESTIONNAIRE_FORM_NAME = 'Health Questionnaire';

    public function syncHealthQuestionnaire(array $requestData)
    {
        $isMetLifeEnabled = (bool) getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_METLIFE);

        if (! $isMetLifeEnabled) {
            LoggerService::warning('MetLife integration is disabled - skipping health questionnaire sync', [
                'quote_uuid' => $requestData['quote_uuid'] ?? 'N/A',
            ]);

            throw new MetLifeException(
                'MetLife integration is disabled',
                MetLifeException::METLIFE_INTEGRATION_DISABLED,
                ['quote_uuid' => $requestData['quote_uuid'] ?? null]
            );
        }

        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($requestData['quote_uuid']));
        LoggerService::info('Starting health questionnaire sync');

        $quote = PersonalQuote::with('customer')->where('uuid', $requestData['quote_uuid'])->first();
        if (! $quote) {
            throw new MetLifeException(
                'Quote not found for UID: '.$requestData['quote_uuid'],
                MetLifeException::QUOTE_NOT_FOUND,
                ['quote_uuid' => $requestData['quote_uuid']]
            );
        }

        $healthQuestionnaire = $this->fetchHealthQuestionnaire($requestData['policy_number']);
        $data = $this->prepareHealthQuestionnaireData($healthQuestionnaire, $requestData['quote_uuid'], $quote);

        $documentType = DocumentType::where('code', DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE)->first();
        if (! $documentType) {
            throw new MetLifeException(
                'Document type not found for code: '.DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE,
                MetLifeException::DOCUMENT_TYPE_NOT_FOUND,
                ['document_type_code' => DocumentTypeCode::LIFE_HEALTH_QUESTIONNAIRE]
            );
        }
        $data['document_type_code'] = $documentType->code;

        $pdfFile = $this->generateHealthQuestionnairePdf($data);

        $document = app(QuoteDocumentService::class)->uploadQuoteDocument(
            $pdfFile,
            $data,
            $quote,
            false,
            false,
            false,
            true
        );

        if (! $document instanceof QuoteDocument) {
            LoggerService::warning('Document upload failed');
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

        LoggerService::info('Health questionnaire sync completed', [
            'document_id' => $document->id,
        ]);

        return $document;
    }

    private function fetchHealthQuestionnaire(string $policyNumber): array
    {
        $metLifeService = app(MetLifeApiService::class);
        $apiVersion = $metLifeService->getApiVersion();
        $endpoint = "/en/api/v{$apiVersion}/policy/{$policyNumber}/";

        $response = $metLifeService->makeRequest($endpoint, 'GET');

        if (! $response['success']) {
            LoggerService::warning('Failed to fetch health questionnaire from API', [
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
        $healthQuestionnaire = $this->extractHealthQuestionnaire($rawData);

        if (empty($healthQuestionnaire)) {
            $availableForms = array_column(($rawData['submitted_data'] ?? [])['fields'] ?? [], 'form_name');
            LoggerService::warning('Health questionnaire not found in response', [
                'policy_number' => $policyNumber,
                'available_forms' => $availableForms,
            ]);
            throw new MetLifeException(
                'Health questionnaire not found for policy: '.$policyNumber,
                MetLifeException::QUESTIONNAIRE_NOT_FOUND,
                ['policy_number' => $policyNumber, 'available_forms' => $availableForms]
            );
        }

        return $healthQuestionnaire;
    }

    private function extractHealthQuestionnaire(array $responseData): ?array
    {
        $fields = ($responseData['submitted_data'] ?? [])['fields'] ?? [];

        foreach ($fields as $field) {
            if ($this->isHealthQuestionnaire($field)) {
                return $field;
            }
        }

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
            return null;
        }

        $firstName = trim($quote->customer->first_name ?? '');
        $lastName = trim($quote->customer->last_name ?? '');

        if (empty($firstName) && empty($lastName)) {
            return null;
        }

        return trim("{$firstName} {$lastName}");
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
            return;
        }

        $previousStatusId = $quote->quote_status_id;

        $quote->update([
            'quote_status_id' => QuoteStatusEnum::ApplicationPending,
            'quote_status_date' => now(),
        ]);

        LoggerService::info('Quote status updated to Application Pending after health questionnaire upload', [
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'previous_status_id' => $previousStatusId,
            'new_status_id' => QuoteStatusEnum::ApplicationPending,
        ]);
    }
}
