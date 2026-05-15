<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EpReversalFailureNotification extends Mailable
{
    use Queueable, SerializesModels;

    private string $logPrefix = 'EpReversalFailureNotification - Mail:';

    /** @var array<string, string> */
    private array $epFailureEmailConfigs = [];

    public function __construct(
        public int $quoteId,
        public int $quoteTypeId,
        public int $etId,
        public string $errorMessage,
    ) {}

    public function build(): self
    {
        LoggerService::info("{$this->logPrefix} Sending EP Sage reversal failure email", extra: [
            'quoteId' => $this->quoteId,
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->etId,
        ]);

        $this->loadEpFailureEmailConfigs();

        return $this->subject('Action required: EP Sage booking reversal failed')
            ->from(...array_slice($this->getRecipientAddress('from'), 0, 2))
            ->to(...array_slice($this->getRecipientAddress('to'), 0, 1))
            ->replyTo(...array_slice($this->getRecipientAddress('reply_to'), 0, 2))
            ->cc($this->getRecipientAddress('cc'))
            ->view('email.ep-sage-reversal-failed', [
                'quoteId' => $this->quoteId,
                'quoteTypeId' => $this->quoteTypeId,
                'etId' => $this->etId,
                'errorMessage' => $this->errorMessage,
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function getRecipientAddress(string $recipientType): array
    {
        $emails = match ($recipientType) {
            'from' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM] ?? '',
            'to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_TO] ?? '',
            'reply_to' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO] ?? '',
            'cc' => $this->epFailureEmailConfigs[ApplicationStorageEnums::EP_FAILURE_EMAIL_CC] ?? '',
            default => '',
        };

        return array_values(array_filter(explode(',', str_replace(' ', '', $emails))));
    }

    private function loadEpFailureEmailConfigs(): void
    {
        $keys = [
            ApplicationStorageEnums::EP_FAILURE_EMAIL_FROM,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_TO,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_REPLY_TO,
            ApplicationStorageEnums::EP_FAILURE_EMAIL_CC,
        ];

        $records = ApplicationStorage::query()
            ->select('value', 'key_name')
            ->where('is_active', ApplicationStorageEnums::ACTIVE)
            ->whereIn('key_name', $keys)
            ->whereNotNull('value')
            ->get();

        $missing = array_diff($keys, $records->pluck('key_name')->toArray());
        if ($missing !== []) {
            throw new \RuntimeException('EP failure email configuration not found for Sage reversal notification');
        }

        $this->epFailureEmailConfigs = $records->pluck('value', 'key_name')->toArray();
    }
}
