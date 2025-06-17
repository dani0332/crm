<?php

namespace App\Exports;

use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\RenewalQuoteProcess;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class RenewalHomeFailedValidationExport implements FromCollection, WithStrictNullComparison
{
    private $renewaUploadLead;
    private $insuranceProvider;

    public function __construct($renewalUploadLead)
    {
        $this->insuranceProvider = 'Insurance Provider';
        $this->renewaUploadLead = $renewalUploadLead;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $failedLeads = RenewalQuoteProcess::where('renewals_upload_lead_id', $this->renewaUploadLead->id)
            ->whereIn('status', [RenewalProcessStatuses::BAD_DATA, RenewalProcessStatuses::VALIDATION_FAILED])
            ->get();

        $exportLeads = collect();

        // Add header row based on renewal import type
        $headerRow = $this->createHeaderRow();
        $exportLeads->push($headerRow);

        // Add failed leads data
        foreach ($failedLeads as $lead) {
            if ($lead->data) {
                $leadData = $lead->data;
                $leadData['errors'] = $lead->validation_errors;
                $exportLeads->push($leadData);
            }
        }

        return $exportLeads;
    }

    /**
     * Create header row based on renewal import type
     */
    private function createHeaderRow(): object
    {
        $headerRow = (object) $this->getCommonHeaders();

        if ($this->renewaUploadLead->renewal_import_type == RenewalsUploadType::CREATE_LEADS) {
            return (object) array_merge((array) $headerRow, $this->getCreateLeadsSpecificHeaders());
        } elseif ($this->renewaUploadLead->renewal_import_type == RenewalsUploadType::UPDATE_LEADS) {
            return (object) array_merge((array) $headerRow, $this->getUpdateLeadsSpecificHeaders());
        }

        return $headerRow;
    }

    /**
     * Get common headers used by both import types
     */
    private function getCommonHeaders(): array
    {
        return [
            'customer_name' => 'Customer Name',
            'email' => 'Customer e-mail',
            'mobile_no' => 'Customer Mobile',
            'quote_type' => 'Insurance Type',
            'advisor' => 'Advisor Email',
            'policy_number' => 'Policy Number',
            'start_date' => 'Policy Start Date',
            'end_date' => 'Policy End date',
            'notes' => 'Notes',
            'errors' => 'Errors',
        ];
    }

    /**
     * Get headers specific to CREATE_LEADS import type
     */
    private function getCreateLeadsSpecificHeaders(): array
    {
        return [
            'insurer' => $this->insuranceProvider,
            'product' => 'Product',
            'product_type' => 'Product Type',
            'batch' => 'Batch',
            'make' => 'Car Make',
            'model' => 'Car Model',
            'year' => 'Model Year',
            'previous_advisor' => 'Previous Advisor Email',
            'object' => 'Object',
            'previous_quote_policy_premium' => 'Gross Premium',
            'source' => 'Sales channel',
        ];
    }

    /**
     * Get headers specific to UPDATE_LEADS import type
     */
    private function getUpdateLeadsSpecificHeaders(): array
    {
        return [
            'current_insurance_provider' => $this->insuranceProvider,
            'you_are_a' => 'You are a',
            'i_live_in_a' => 'I live in a (Type of Property)',
            'occupancy_status_for_owners' => 'Occupancy Status for Owners',
            'location_area' => 'Location Area',
            'cover_required' => 'Cover Required',
            'contents' => 'Contents',
            'personal_belongings' => 'Personal Belongings',
            'building' => 'Building',
            'insurance_provider' => $this->insuranceProvider,
            'plan_name' => 'Plan Name',
            'claims_history' => 'Claims History',
            'premium' => 'Premium',
            'insurer_quote_no' => 'Insurer Quote No.',
            'previous_advisor_email' => 'Previous Advisor Email',
        ];
    }
}
