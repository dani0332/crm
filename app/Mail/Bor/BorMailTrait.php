<?php

namespace App\Mail\Bor;

use App\Enums\QuoteTypeId;
use App\Models\InsuranceProvider;

trait BorMailTrait
{
    /**
     * Get customer display name based on customer type
     */
    protected function getCustomerName(bool $isFirstName = false): string
    {
        $firstName = $this->customerData['first_name'] ?? '';
        $lastName = $this->customerData['last_name'] ?? '';
        $name = trim($firstName.' '.$lastName);

        if ($isFirstName) {
            return $firstName;
        }

        return $name ?: $this->borLog->insurer_name ?: 'Valued Customer';
    }

    /**
     * Get subject line based on quote type and provider
     */
    protected function getSubjectLine($personalQuote, string $quoteType, string $prefix = ''): string
    {
        $provider = InsuranceProvider::find($this->borLog->insurance_provider_id);
        $name = $this->getCustomerName();

        // Special handling for Sukoon/OIC car insurance
        if ($personalQuote->quote_type_id === QuoteTypeId::Car && 
            $provider && 
            (strtolower($provider->code) === 'oic' || stripos($provider->text, 'sukoon') !== false)) {
            return $prefix.'BOR '.$this->borLog->chassis_number.' - '.$name.($prefix ? '' : ' '.$personalQuote->code);
        }

        // Default subject line
        return $prefix.$name.' For signature - Broker Appointment Letter '.$personalQuote->code;
    }
}

