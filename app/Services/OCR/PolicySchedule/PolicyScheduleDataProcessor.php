<?php

declare(strict_types=1);

namespace App\Services\OCR\PolicySchedule;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use App\Services\OCR\OcrValidator;
use Exception;
use Illuminate\Database\Eloquent\Model;

class PolicyScheduleDataProcessor
{
    use OcrUtils, OcrValidator;

    private array $ocr_values = [];
    private array $fields_updated = [
        'quote' => [],
        'payment' => [],
    ];
    private bool $isSendUpdateEligibleForOCR = false;
    private string $providerCode = '';

    public function __construct(
        private Model $quote,
        private object $data,
        bool $isSendUpdateEligibleForOCR = false,
        string $providerCode = '',
    ) {
        $this->isSendUpdateEligibleForOCR = $isSendUpdateEligibleForOCR;
        $this->providerCode = $providerCode;
    }

    public function processPolicyScheduleData(): bool
    {
        try {
            LoggerService::info(self::class.': OCR fillPolicySchedule started');
            LoggerService::info(self::class.': OCR Policy Schedule data received', extra: ['ocr_data' => json_decode(json_encode($this->data), true)]);

            if ($this->isSendUpdateEligibleForOCR) {
                $this->handleSendUpdate();
            } else {
                $this->handleRegularQuote();
            }

            LoggerService::info(self::class.': OCR fillPolicySchedule completed', extra: [
                'ocr_values' => $this->ocr_values,
                'fields_updated' => $this->fields_updated,
                'is_send_update_eligible' => $this->isSendUpdateEligibleForOCR,
            ]);

            return true;

        } catch (Exception $e) {
            LoggerService::error(self::class.': OCR fillPolicySchedule failed', exception: $e);

            return false;
        }
    }

    private function handleSendUpdate()
    {
        $dataToUpdate = [];

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_number')) {
            $policyNumber = $this->resolveProp($this->data, 'policyNumber') ?? $this->quote->policy_number;
            $dataToUpdate['policy_number'] = $policyNumber;

            $this->ocr_values['policy_number'] = [
                'ocr_value' => $this->resolveProp($this->data, 'policyNumber'),
                'previous_value' => $this->quote->policy_number,
                'final_value' => $policyNumber,
            ];
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_start_date') && $this->isFieldEnabled($this->providerCode, 'quote.policy_expiry_date')) {
            $startDateValue = $this->resolveProp($this->data, 'policyStartDate') ?? $this->resolveProp($this->data, 'startDate');
            $expiryDateValue = $this->resolveProp($this->data, 'policyExpiryDate') ?? $this->resolveProp($this->data, 'expiryDate');

            $startDate = $this->parseDate($startDateValue, $this->quote->start_date);
            $expiryDate = $this->parseDate($expiryDateValue, $this->quote->expiry_date);

            $dataToUpdate['start_date'] = $startDate;
            $dataToUpdate['expiry_date'] = $expiryDate;

            $this->ocr_values['start_date'] = [
                'ocr_value' => $startDateValue,
                'previous_value' => $this->quote->start_date,
                'final_value' => $startDate,
            ];

            $this->ocr_values['expiry_date'] = [
                'ocr_value' => $expiryDateValue,
                'previous_value' => $this->quote->expiry_date,
                'final_value' => $expiryDate,
            ];
        }

        if (! empty($dataToUpdate)) {
            $this->quote->update($dataToUpdate);
            $this->fields_updated['quote'] = array_keys($dataToUpdate);
            LoggerService::info(self::class.': OCR fillPolicySchedule updated quote fields (send update)', extra: ['fields_updated' => $this->fields_updated['quote']]);
        }
    }

    private function handleRegularQuote()
    {
        $dataToUpdate = [];

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_number')) {
            $policyNumber = $this->resolveProp($this->data, 'policyNumber') ?? $this->quote->policy_number;
            $dataToUpdate['policy_number'] = $policyNumber;

            $this->ocr_values['policy_number'] = [
                'ocr_value' => $this->resolveProp($this->data, 'policyNumber'),
                'previous_value' => $this->quote->policy_number,
                'final_value' => $policyNumber,
            ];
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_start_date') && $this->isFieldEnabled($this->providerCode, 'quote.policy_expiry_date')) {
            $policyStartDateValue = $this->resolveProp($this->data, 'policyStartDate') ?? $this->resolveProp($this->data, 'startDate');
            $policyExpiryDateValue = $this->resolveProp($this->data, 'policyExpiryDate') ?? $this->resolveProp($this->data, 'expiryDate');

            $policyStartDate = $this->parseDate($policyStartDateValue, $this->quote->policy_start_date);
            $policyExpiryDate = $this->parseDate($policyExpiryDateValue, $this->quote->policy_expiry_date);

            $dataToUpdate['policy_start_date'] = $policyStartDate;
            $dataToUpdate['policy_expiry_date'] = $policyExpiryDate;

            $this->ocr_values['policy_start_date'] = [
                'ocr_value' => $policyStartDateValue,
                'previous_value' => $this->quote->policy_start_date,
                'final_value' => $policyStartDate,
            ];

            $this->ocr_values['policy_expiry_date'] = [
                'ocr_value' => $policyExpiryDateValue,
                'previous_value' => $this->quote->policy_expiry_date,
                'final_value' => $policyExpiryDate,
            ];
        }

        if (! empty($dataToUpdate)) {
            $this->quote->update($dataToUpdate);
            $this->fields_updated['quote'] = array_keys($dataToUpdate);
            LoggerService::info(self::class.': OCR fillPolicySchedule updated quote fields (regular)', extra: ['fields_updated' => $this->fields_updated['quote']]);
        }
    }

    public function getProcessingSummary(): array
    {
        try {
            return [
                'status' => 'success',
                'document_type' => 'Policy Schedule',
                'ocr_values' => $this->ocr_values,
                'fields_updated' => $this->fields_updated,
                'is_send_update_eligible' => $this->isSendUpdateEligibleForOCR,
            ];
        } catch (Exception $e) {
            LoggerService::error(self::class.': Failed to get Policy Schedule processing summary', exception: $e);

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve Policy Schedule processing summary',
            ];
        }
    }
}
