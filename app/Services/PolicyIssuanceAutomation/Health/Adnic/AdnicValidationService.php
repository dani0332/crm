<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Services\Logger\LoggerService;

class AdnicValidationService
{
    /**
     * Validate required data for policy issuance
     *
     * @param  mixed  $quote
     */
    public function validateRequiredData($quote): array
    {
        $customer = $quote->customer ?? null;
        $nationality = $quote->nationality ?? null;
        $emirateOfRegistration = $quote?->cyberQuote?->emirateOfRegistration ?? null;

        $missing = [];

        if (! $quote->payments) {
            $missing[] = 'payments';
        }

        if (! $quote->cyberPlanDetail) {
            $missing[] = 'cyber plan detail';
        }

        $nationality == null && $missing[] = 'nationality';
        $emirateOfRegistration == null && $missing[] = 'emirate of registration';

        // Only check emirates id if customer exists (not null)
        if ($customer === null) {
            $missing[] = 'customer';
        } else {
            empty($customer->emirates_id_number) && $missing[] = 'emirates id number';
            $customer->dob === null && $missing[] = 'dob';
        }

        if (! empty($missing)) {
            LoggerService::error('Missing required data', extra: [
                'has_payments' => (bool) $quote->payments,
                'has_plan_detail' => (bool) $quote->cyberPlanDetail,
                'has_emirates_id' => (bool) ($customer?->emirates_id_number),
                'has_nationality' => (bool) ($nationality),
                'has_dob' => (bool) ($customer?->dob),
                'has_emirate_of_registration' => (bool) ($emirateOfRegistration),
            ]);

            $missingDesc = implode(', ', $missing);

            return [
                'status' => false,
                'error' => "Missing required data: $missingDesc",
                'message' => "Missing required data: $missingDesc",
            ];
        }

        return ['status' => true];
    }

}
