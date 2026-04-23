<?php

namespace App\Services;

use App\Enums\ProcessStatusCode;
use App\Services\Logger\LoggerService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Log;

class PostMarkService extends BaseService
{
    public function __construct(
        private readonly EmailStatusService $emailStatusService,
        private ClientInterface $client = new Client,
    ) {
        parent::__construct();
    }

    public function sendEmail($body)
    {
        $responseCode = 0;
        $responseBodyString = null;

        try {
            $headers = [
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => config('constants.POSTMARK_TOKEN'),
                'Content-Type' => 'application/json',
            ];

            $clientRequest = $this->client->request(
                'POST',
                config('constants.POSTMARK_URL'),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 100,
                ]
            );

            $responseCode = $clientRequest->getStatusCode();
            $responseBodyString = $clientRequest->getBody()->getContents();
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'PostMark Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' Class: '.get_class();
            Log::error($responseDetail);
        }

        if ($responseCode === 200 && $responseBodyString !== null) {
            try {
                $this->afterSuccessfulPostmarkSend($body, $responseBodyString);
            } catch (Exception $ex) {
                $requestPayload = $this->requestPayloadAsArray($body);
                LoggerService::error(
                    'PostMark afterSuccessfulPostmarkSend failed',
                    [],
                    $ex,
                    [
                        'processing_stage' => 'afterSuccessfulPostmarkSend',
                        'postmark_response_body' => $responseBodyString,
                        'postmark_message_id' => $this->postmarkMessageIdFromResponseJson($responseBodyString),
                        'metadata' => $this->postmarkMetadataFromPayload($requestPayload),
                    ],
                );
            }
        }

        return $responseCode;
    }

    private function afterSuccessfulPostmarkSend(mixed $body, string $responseBodyString): void
    {
        $requestPayload = $this->requestPayloadAsArray($body);
        $metadata = $this->postmarkMetadataFromPayload($requestPayload);

        if ($requestPayload === null || $metadata === null || ! $this->metadataHasQuoteIdAndTypeId($metadata)) {
            return;
        }

        $messageId = $this->postmarkMessageIdFromResponseJson($responseBodyString);
        if ($messageId === null) {
            return;
        }

        $this->emailStatusService->logEpEmailStatuses(
            $this->validatedPayloadForEpEmailStatusLog($requestPayload, $metadata, $messageId)
        );
    }

    private function postmarkMessageIdFromResponseJson(string $responseBodyString): ?string
    {
        $responseData = json_decode($responseBodyString, true);
        if (! is_array($responseData)) {
            return null;
        }

        $messageId = trim((string) ($responseData['MessageID'] ?? ''));

        return $messageId !== '' ? $messageId : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestPayloadAsArray(mixed $body): ?array
    {
        $payload = null;
        if (is_array($body)) {
            $payload = $body;
        } elseif (is_string($body)) {
            $decoded = json_decode($body, true);
            $payload = is_array($decoded) ? $decoded : null;
        } elseif (is_object($body)) {
            $asArray = json_decode(json_encode($body), true);
            $payload = is_array($asArray) ? $asArray : null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function postmarkMetadataFromPayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $meta = $payload['Metadata'] ?? $payload['metadata'] ?? null;

        return is_array($meta) ? $meta : null;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function metadataHasQuoteIdAndTypeId(?array $metadata): bool
    {
        if ($metadata === null) {
            return false;
        }

        $quoteId = $metadata['quote_id'] ?? $metadata['quoteId'] ?? null;
        $quoteTypeId = $metadata['quote_type_id'] ?? $metadata['quoteTypeId'] ?? null;

        return $this->positiveIntString($quoteId) !== null
            && $this->positiveIntString($quoteTypeId) !== null;
    }

    private function positiveIntString(mixed $value): ?string
    {
        if (is_int($value)) {
            return $value > 0 ? (string) $value : null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        $intVal = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $intVal === false ? null : (string) $intVal;
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function validatedPayloadForEpEmailStatusLog(array $requestPayload, array $metadata, string $messageId): array
    {
        $quoteId = $this->positiveIntString($metadata['quote_id'] ?? $metadata['quoteId'] ?? null);
        $quoteTypeId = $this->positiveIntString($metadata['quote_type_id'] ?? $metadata['quoteTypeId'] ?? null);

        $subject = $metadata['subject']
            ?? ((isset($requestPayload['Subject']) && is_string($requestPayload['Subject']))
                ? $requestPayload['Subject']
                : null);

        $normalizedMeta = array_merge($metadata, [
            'quote_id' => $quoteId,
            'quote_type_id' => $quoteTypeId,
            'subject' => $subject,
        ]);

        $to = $requestPayload['To'] ?? null;

        return [
            'MessageID' => $messageId,
            'RecordType' => ProcessStatusCode::IN_PROGRESS,
            'Metadata' => $normalizedMeta,
            'Recipient' => is_string($to) ? $to : null,
            'Subject' => $subject,
        ];
    }
}
