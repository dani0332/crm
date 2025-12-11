<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use Carbon\Carbon;

class NgiRequestBuilder
{
    /**
     * Build payload for CreatePolicyFromQuote API
     *
     * @param mixed $quote
     * @param mixed $customer
     * @param mixed $deviceQuote
     * @param mixed $payment
     * @return array
     */
    public function buildCreatePolicyFromQuotePayload($quote, $customer, $deviceQuote, $payment): array
    {
        return [
            'client_reference_number' => $quote->code ?? null,
            'quote_reference_number' => $quote->insurer_quote_number,
            'payment_reference_number' => $payment?->code ?? null,
            'transaction_country' => 'UAE',
            'sales_info' => [
                'policy_sold_date' => Carbon::now()->format('Y-m-d'),
                'policy_sold_location' => null,
                'policy_sold_salesman' => null,
            ],
            'customer_info' => [
                'customer_fname' => $quote->first_name ?? null,
                'customer_lname' => $quote->last_name ?? null,
                'customer_mobile_no' => $quote->mobile_no ?? null,
                'customer_whatsapp_no' => null,
                'customer_email_id' => $quote->email ?? null,
                'customer_id_type' => 'EID',
                'customer_id_no' => $this->formatEmiratesId($customer?->emirates_id_number),
                'customer_id_expiry_date' => $customer?->emirates_id_expiry_date
                    ? Carbon::parse($customer->emirates_id_expiry_date)->format('Y-m-d')
                    : null,
                'customer_address' => $customer?->address ?? null,
                'customer_address_city' => null,
                'customer_address_country' => 'UAE',
            ],
            'device_info' => [
                'imei_no' => $deviceQuote?->imei_number ?? null,
                'serial_no' => $deviceQuote?->serial_number ?? '',
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
     *
     * @param string|null $emiratesId
     * @return string|null
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
     *
     * @return array
     */
    public function buildCreatePolicyHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
