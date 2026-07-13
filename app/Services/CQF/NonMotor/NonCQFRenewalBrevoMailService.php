<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor;

use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\Http;

class NonCQFRenewalBrevoMailService
{
    private string $apiKey;
    private string $url;

    public function __construct()
    {
        $this->apiKey = config('constants.SENDINBLUE_KEY');
        $this->url = config('constants.SIB_URL');
    }

    /**
     * Send a transactional email via Brevo.
     *
     * @return array{sent: int, code: int, object: object, respBody: string, response: string}
     */
    public function send(array $body): array
    {
        $headers = [
            'Accept' => 'application/json',
            'api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        $to = $body['to'] ?? null;
        $to !== null && LoggerService::info(self::class.' - Mail Request to details ----- '.json_encode($to));

        $response = Http::withHeaders($headers)
            ->timeout((int) config('constants.LMS_EMAILS_TIMEOUT'))
            ->post($this->url, $body);

        $result = [
            'ok' => $response->ok(),
            'code' => $response->status(),
            'object' => $response->object(),
            'respBody' => $response->body(),
            'response' => "{$response->status()} {$response->body()}",
            'sent' => 0,
        ];

        if ($result['code'] === 201) {
            $result['sent'] = 1;
        } else {
            LoggerService::error(self::class.' - Mail Sent Failed', ['result' => $result]);
        }

        return $result;
    }
}
