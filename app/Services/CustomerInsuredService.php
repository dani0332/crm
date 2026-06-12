<?php

namespace App\Services;

use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\InsuredKyc;

class CustomerInsuredService
{
    public function getInsuredDetails($quoteId): ?array
    {
        $customerInsured = CustomerInsured::where('quote_request_id', $quoteId)?->first();

        if (! $customerInsured) {
            return null;
        }

        return $this->getInsuredData($customerInsured->insured_id);
    }

    private function getInsuredData($insuredId): ?array
    {
        // Fetch only required columns
        $insured = Insured::select('first_name', 'last_name', 'id_number')->find($insuredId);
        $insuredKyc = InsuredKyc::select('id_issuance_date', 'id_expiry_date')
            ->where('insured_id', $insuredId)
            ->first();

        if (! $insured && ! $insuredKyc) {
            return null;
        }

        // Merge both table data
        $result = array_merge(
            $insured->toArray() ?? [],
            $insuredKyc->toArray() ?? []
        );

        return ! empty($result) ? $result : null;
    }
}
