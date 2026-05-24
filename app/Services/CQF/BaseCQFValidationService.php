<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Enums\LeadSourceEnum;
use App\Models\PersonalQuote;
use App\Repositories\SendUpdateLogRepository;
use App\Services\CQF\Contracts\CQFValidationInterface;
use App\Services\CRUDService;
use App\Services\Logger\LoggerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

/**
 * Base CQF validation: common rules required by both Motor and Non-motor FRs.
 * LOB-specific validation should be composed or piped after this.
 */
class BaseCQFValidationService implements CQFValidationInterface
{
    /**
     * Base validation rules (common to Motor and Non-motor).
     *
     * @return array<string, array<int, string>>
     */
    protected function getBaseRules(): array
    {
        return [
            'policy_number' => ['required'],
            'policy_expiry_date' => ['required', 'date'],
            'first_name' => ['required'],
            'email' => ['required', 'email'],
            'mobile_no' => ['required'],
        ];
    }

    public function validateQuote(Model $quote): array
    {
        $rules = $this->getBaseRules();
        $validator = Validator::make($quote->toArray(), $rules, $this->getValidationMessages());

        $errors = [];

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
        }

        if (! empty($errors)) {
            LoggerService::info(self::class.' - Quote validation failed', [
                'errors' => $errors,
                'quote_uuid' => $quote->uuid ?? null,
            ]);

            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'errors' => [],
        ];
    }

    /**
     * Default duplicate check for PersonalQuote (renewal upload source).
     * Subclasses (e.g. Bike for CarQuote) override for LOB-specific logic.
     */
    public function isDuplicateQuote(Model $quote): bool
    {
        if (! $quote instanceof PersonalQuote) {
            return false;
        }

        return PersonalQuote::where('previous_quote_id', $quote->id)
            ->where('previous_quote_policy_number', $quote->policy_number)
            ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
            ->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->exists();
    }

    public function getValidationMessages(): array
    {
        return [
            'policy_number.required' => 'Policy number is required.',
            'policy_expiry_date.required' => 'Policy expiry date is required.',
            'policy_expiry_date.date' => 'Policy expiry date must be a valid date.',
            'first_name.required' => 'Customer name is required.',
            'email.required' => 'Customer email is required.',
            'email.email' => 'Customer email must be a valid email address.',
            'mobile_no.required' => 'Customer mobile is required.',
        ];
    }

    public function checkInslyRenewal(Model $quote): bool
    {
        if ($quote->source !== LeadSourceEnum::INSLY) {
            return true; // Only apply Insly renewal criteria for quotes from Insly
        }
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if (! $hasPolicyIssuedStatus) {
            return false;
        }

        $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);

        $endorsementFinancial = false;
        $policyPeriodExtension = false;
        $isUpdateBooked = false;

        if (! empty($sendUpdateLogs)) {
            foreach ($sendUpdateLogs as $log) {
                if ($log->isEndorsementFinancial()) {
                    $endorsementFinancial = true;
                    LoggerService::info(self::class.' - Endorsement financial found', [
                        'policy_number' => $quote->policy_number,
                    ]);
                }

                if ($log->isPolicyPeriodExtension()) {
                    LoggerService::info(self::class.' - Policy period extension found', [
                        'policy_number' => $quote->policy_number,
                    ]);
                    $policyPeriodExtension = true;
                }

                if ($log->isUpdateBooked()) {
                    LoggerService::info(self::class.' - Update booked found', [
                        'policy_number' => $quote->policy_number,
                    ]);
                    $isUpdateBooked = true;
                }
            }
        }

        return $endorsementFinancial && $policyPeriodExtension && $isUpdateBooked;
    }
}
