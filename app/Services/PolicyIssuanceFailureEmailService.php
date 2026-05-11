<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApplicationStorageEnums;

class PolicyIssuanceFailureEmailService
{
    /**
     * Cache of normalized email lists keyed by application storage key.
     *
     * @var array<string, array<string>>
     */
    private array $cachedEmails = [];

    public function __construct(
        private readonly ApplicationStorageService $applicationStorageService,
    ) {}

    public function isPolicyIssuanceFailureEmail(?string $email): bool
    {
        return $this->matchesFailureEmail($email, ApplicationStorageEnums::IMCRM_POLICY_ISSUANCE_FAILURE_EMAIL);
    }

    public function isDocumentDownloadFailureEmail(?string $email): bool
    {
        return $this->matchesFailureEmail($email, ApplicationStorageEnums::IMCRM_DOC_DOWNLOAD_FAILURE_EMAIL);
    }

    public function isDocumentUploadFailureEmail(?string $email): bool
    {
        return $this->matchesFailureEmail($email, ApplicationStorageEnums::IMCRM_DOC_UPLOAD_FAILURE_EMAIL);
    }

    public function isBookPolicyFailureEmail(?string $email): bool
    {
        return $this->matchesFailureEmail($email, ApplicationStorageEnums::IMCRM_BOOK_POLICY_FAILURE_EMAIL);
    }

    private function matchesFailureEmail(?string $email, string $storageKey): bool
    {
        if (! $email) {
            return false;
        }

        $normalizedEmail = strtolower(trim($email));

        $allowedEmails = $this->getNormalizedEmails($storageKey);
        if ($allowedEmails === []) {
            return false;
        }

        return in_array($normalizedEmail, $allowedEmails, true);
    }

    /**
     * Load and normalize the stored email list for the provided key.
     *
     * @return array<string>
     */
    private function getNormalizedEmails(string $storageKey): array
    {
        if (array_key_exists($storageKey, $this->cachedEmails)) {
            return $this->cachedEmails[$storageKey];
        }

        $storedValue = $this->applicationStorageService->getValueByKey($storageKey);
        if (! is_string($storedValue) || trim($storedValue) === '') {
            return $this->cachedEmails[$storageKey] = [];
        }

        $emails = array_filter(
            array_map(
                static fn (string $value): string => strtolower(trim($value)),
                explode(',', $storedValue),
            ),
            static fn (string $value): bool => $value !== '',
        );

        return $this->cachedEmails[$storageKey] = array_values($emails);
    }
}
