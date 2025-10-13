<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Services\Logger\LoggerService;
use Exception;

class MetLifeValidationService
{
    public function isIntegrationEnabled(): bool
    {
        return (bool) getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_METLIFE);
    }

    public function shouldValidatePayment(?string $providerCode): bool
    {
        $isMetLifeEnabled = $this->isIntegrationEnabled();
        $providerCode = $providerCode;

        return $providerCode !== InsuranceProviderEnum::MTL->value || ! $isMetLifeEnabled;
    }

    public function isSessionValid(?string $sessionId, ?int $sessionCreatedAt, int $sessionTimeout): bool
    {
        if (! $sessionId || ! $sessionCreatedAt) {
            return false;
        }

        $sessionAge = time() - $sessionCreatedAt;

        return $sessionAge < $sessionTimeout;
    }

    public function isCsrfTokenValid(?string $csrfToken, ?int $csrfTokenCreatedAt, int $csrfTokenRefreshInterval): bool
    {
        if (! $csrfToken || ! $csrfTokenCreatedAt) {
            return false;
        }

        $tokenAge = time() - $csrfTokenCreatedAt;

        return $tokenAge < $csrfTokenRefreshInterval;
    }

    public function validateResponse(array $response): bool
    {
        return isset($response['status']) && $response['status'] === true;
    }

    public function handleException(Exception $e, string $action): array
    {
        LoggerService::warning("MetLife API - {$action} failed", [
            'error' => $e->getMessage(),
        ]);

        return [
            'status' => false,
            'message' => "{$action} failed",
            'error' => $e->getMessage(),
        ];
    }

    public function isProviderMetLife(string $providerCode): bool
    {
        return $providerCode === InsuranceProviderEnum::MTL->value;
    }
}
