<?php

namespace App\Mail\Bor;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use App\Models\InsuranceProvider;

trait BorMailTrait
{
    /**
     * Get customer display name based on customer type
     */
    protected function getCustomerName(bool $isFirstName = false, bool $isInsurerNotification = false): string
    {
        $firstName = $this->customerData['first_name'] ?? '';
        $lastName = $this->customerData['last_name'] ?? '';
        $name = trim($firstName.' '.$lastName);

        if($isInsurerNotification) {
            if($this->borLog->customer_type === CustomerTypeEnum::Entity) {
                return $this->borLog->company_name ?? $this->customerData['company_name'] ?? 'Valued Company';
            }
            return $this->borLog->insurer_name ?? $name ?: 'Valued Customer';
        }

        if ($isFirstName) {
            return $firstName;
        }

        return $name ?: $this->borLog->insurer_name ?: 'Valued Customer';
    }

    /**
     * Get subject line based on quote type and provider
     */
    protected function getSubjectLine($personalQuote, $isInsurerNotification = false): string
    {
        $provider = InsuranceProvider::find($this->borLog->insurance_provider_id);
        $name = $this->getCustomerName(false, $isInsurerNotification);

        // Special handling for Sukoon/OIC car insurance
        if ($personalQuote->quote_type_id === QuoteTypeId::Car &&
            $provider &&
            (strtolower($provider->code) === strtolower(InsuranceProviderEnum::OIC->value) || stripos($provider->text, strtolower(InsuranceProviderEnum::getTextByCode(InsuranceProviderEnum::OIC->value))) !== false)) {
            if($isInsurerNotification) {
                return 'Request for BOR '.$this->borLog->chassis_number.' - '.$name.' '.$personalQuote->code;
            }
            return 'BOR '.$this->borLog->chassis_number.' - '.$name.' '.$personalQuote->code;
        }

        if ($isInsurerNotification) {
            return 'Request for BOR ' . $name . ' ' . $personalQuote->code;
        }

        // Default subject line
        return $name.' For signature - Broker Appointment Letter '.$personalQuote->code;
    }


    /**
     * Get Bird workflow URL for BOR request emails
     */
    protected function getBirdWorkflowUrl()
    {
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_BOR_WORKFLOW_URL)->first();

        return $workflowConfig ? $workflowConfig->value : null;
    }
}
