<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\ApplicationStorageEnums;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Services\ApplicationStorageService;
use Carbon\Carbon;

class NgiRequestBuilder
{
    private const APPLICATION_JSON = 'application/json';

    public function __construct(private readonly ApplicationStorageService $applicationStorageService) {}

    /**
     * Build payload for CreatePolicyFromQuote API
     *
     * @param  mixed  $quote
     * @param  mixed  $customer
     * @param  mixed  $deviceQuote
     * @param  mixed  $payment
     */
    public function buildCreatePolicyFromQuotePayload($quote, $customer, $deviceQuote, $payment, $latestInsured): array
    {

        $emiratesIdNumber = ($latestInsured?->id_type == 'emiratesId') ? $latestInsured?->id_number : ($customer?->emirates_id_number ?? null);
        $paymentReferenceNumber = match (true) {
            $payment instanceof Payment => $payment?->paymentSplits?->first()?->paymentCharges?->transaction_id ?? null,
            $payment instanceof PaymentSplits => $payment?->paymentCharges?->transaction_id ?? null,
            default => null,
        } ?? '';

        $customerMobileNumber = $this->applicationStorageService->getValueByKey(ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_MOBILE_NO) ?: null;
        $customerEmailId = $this->applicationStorageService->getValueByKey(ApplicationStorageEnums::CHIEF_DEPUTY_OFFICER_EMAIL_ID) ?: null;

        return [
            'client_reference_number' => 'DEV-********',
            'quote_reference_number' => $quote->insurer_quote_number,
            'payment_reference_number' => $paymentReferenceNumber,
            'transaction_country' => 'UAE',
            'sales_info' => [
                'policy_sold_date' => Carbon::now()->format('Y-m-d'),
                'policy_sold_location' => null,
                'policy_sold_salesman' => null,
            ],
            'customer_info' => [
                'customer_fname' => $quote->first_name ?? null,
                'customer_lname' => $quote->last_name ?? null,
                'customer_mobile_no' => $customerMobileNumber,
                'customer_whatsapp_no' => null,
                'customer_email_id' => $customerEmailId,
                'customer_id_type' => 'EID',
                'customer_id_no' => $this->formatEmiratesId($emiratesIdNumber),
                'customer_id_expiry_date' => null,
                'customer_address' => null,
                'customer_address_city' => null,
                'customer_address_country' => null,
            ],
            'device_info' => [
                'imei_no' => $deviceQuote?->imei ?? null,
                'serial_no' => '',
                'mw_start_date' => $quote->policy_start_date
                    ? Carbon::parse($quote->policy_start_date)->format('Y-m-d')
                    : Carbon::now()->format('Y-m-d'),
                'mw_end_date' => $quote->policy_expiry_date
                    ? Carbon::parse($quote->policy_expiry_date)->format('Y-m-d')
                    : Carbon::now()->addYear()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Format Emirates ID to expected format
     */
    private function formatEmiratesId(?string $emiratesId): ?string
    {
        if (! $emiratesId) {
            return null;
        }

        // Remove any existing dashes and spaces
        $cleanId = preg_replace('/[-\s]/', '', $emiratesId);

        // Format as XXX-XXXXXXXXXXXX-X if it's 15 digits
        if (strlen($cleanId) === 15) {
            return sprintf('%s-%s-%s',
                substr($cleanId, 0, 3),
                substr($cleanId, 3, 11),
                substr($cleanId, 14, 1)
            );
        }

        return $emiratesId;
    }

    /**
     * Build headers required for CreatePolicyFromQuote API
     */
    public function buildCreatePolicyHeaders(): array
    {
        return [
            'Accept' => self::APPLICATION_JSON,
        ];
    }
}
