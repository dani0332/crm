<?php

declare(strict_types=1);

namespace App\Services\CQF;

use App\Enums\CarRegistrationType;
use App\Enums\LeadSourceEnum;
use App\Models\CarQuote;
use App\Services\CRUDService;
use App\Services\Logger\LoggerService;
use App\Repositories\SendUpdateLogRepository;
use Illuminate\Support\Facades\Validator;

class CarCQFValidationService
{
    public function validateQuote(CarQuote $quote): array
    {
        $validator = Validator::make($quote->toArray(), [
            'policy_number' => ['required'],
            'policy_expiry_date' => ['required', 'date'],
            'first_name' => ['required'],
            'email' => ['required', 'email'],
            'mobile_no' => ['required'],
            'car_make_id' => ['required'],
            'car_model_id' => ['required'],
            'registration_type' => ['required', 'in:'.CarRegistrationType::PERSONAL.','.CarRegistrationType::COMPANY],
        ], $this->getValidationMessages());

        $errors = [];

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            foreach ($errors as $field => $message) {
                $errors[$field] = $message[0];
            }
        }

        if (!empty($errors)) {
            LoggerService::error(self::class.' - Quote validation failed', [
                'errors' => $errors, 
                'quote_uuid' => $quote->uuid ?? null
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

    public function isDuplicateQuote(CarQuote $quote): bool
    {
        return CarQuote::where('previous_quote_id', $quote->id)
            ->where('previous_quote_policy_number', $quote->policy_number)
            ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
            ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
            ->exists();
    }

    public function checkInslyRenewal(CarQuote $quote): bool
    {
        // Check if the quote has at least one status of policy issued
        $hasPolicyIssuedStatus = app(CRUDService::class)->hasAtleastOneStatusPolicyIssued($quote);

        if (!$hasPolicyIssuedStatus) {
            return false;
        }

        // Retrieve send update options and logs
        $sendUpdateLogs = SendUpdateLogRepository::findByQuoteUuid($quote->uuid);

        $endorsementFinancial = false;
        $policyPeriodExtension = false;
        $isUpdateBooked = false;

        if ($quote->source === LeadSourceEnum::INSLY && !empty($sendUpdateLogs)) {
            foreach ($sendUpdateLogs as $log) {
                if ($log->isEndorsementFinancial()) {
                    $endorsementFinancial = true;
                    LoggerService::info(self::class.' - Endorsement financial found', [
                        'policy_number' => $quote->policy_number
                    ]);
                }

                if ($log->isPolicyPeriodExtension()) {
                    LoggerService::info(self::class.' - Policy period extension found', [
                        'policy_number' => $quote->policy_number
                    ]);
                    $policyPeriodExtension = true;
                }

                if ($log->isUpdateBooked()) {
                    LoggerService::info(self::class.' - Update booked found', [
                        'policy_number' => $quote->policy_number
                    ]);
                    $isUpdateBooked = true;
                }
            }
        }

        return $endorsementFinancial && $policyPeriodExtension && $isUpdateBooked;
    }

    public function getValidationMessages(): array
    {
        return [
            'policy_number.required' => 'Policy number is required.',
            'source.required' => 'Source is required.',
            'policy_expiry_date.required' => 'Policy expiry date is required.',
            'policy_expiry_date.date' => 'Policy expiry date must be a valid date.',
            'first_name.required' => 'Customer name is required.',
            'email.required' => 'Customer email is required.',
            'email.email' => 'Customer email must be a valid email address.',
            'mobile_no.required' => 'Customer mobile is required.',
            'car_make_id.required' => 'Car make is required.',
            'car_model_id.required' => 'Car model is required.',
            'registration_type.required' => 'Registration type is required.',
        ];
    }
}
