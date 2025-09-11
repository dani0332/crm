<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class RtaTransactionTypeService
{
    /**
     * RTA Transaction Type constants
     */
    public const RTA_NEW_VEHICLE_REGISTRATION = 'RTT01';

    public const RTA_CHANGE_VEHICLE_OWNERSHIP = 'RTT03';
    public const RTA_VEHICLE_RENEWAL = 'RTT04';
    public const RTA_UPDATE_REGISTRATION = 'RTT07';
    public const RTA_VEHICLE_RENEWAL_WITH_CHANGE_NUMBER = 'RTT10';

    /**
     * Policy duration in months
     */
    public const POLICY_DURATION_MONTHS = 13;

    /**
     * GIG Insurance provider code
     */
    public const GIG_PROVIDER_CODE = 'AXA';

    /**
     * Get field configuration based on RTA transaction type
     */
    public function getFieldConfiguration(string $rtaTransactionType, bool $isGigRenewal = false): array
    {
        $config = [
            'policy_effective_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
            'policy_expiry_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
            'certificate_start_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
            'certificate_end_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
            'plate_code' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
            'plate_number' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
            'rta_plate_category' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
        ];

        switch ($rtaTransactionType) {
            case self::RTA_NEW_VEHICLE_REGISTRATION:
                $config = array_merge($config, [
                    'policy_effective_date' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'policy_expiry_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                    'certificate_start_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                    'certificate_end_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                    'plate_code' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => true, 'optional' => false],
                    'plate_number' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => true, 'optional' => false],
                    'rta_plate_category' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => true],
                ]);
                break;

            case self::RTA_VEHICLE_RENEWAL:
                if ($isGigRenewal) {
                    $config = array_merge($config, [
                        'policy_effective_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                        'policy_expiry_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                        'certificate_start_date' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                        'certificate_end_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                        'plate_code' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                        'plate_number' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                        'rta_plate_category' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    ]);
                } else {
                    $config = array_merge($config, [
                        'policy_effective_date' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                        'policy_expiry_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                        'certificate_start_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                        'certificate_end_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                        'plate_code' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                        'plate_number' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                        'rta_plate_category' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    ]);
                }
                break;

            case self::RTA_CHANGE_VEHICLE_OWNERSHIP:
                $config = array_merge($config, [
                    'policy_effective_date' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'policy_expiry_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                    'certificate_start_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                    'certificate_end_date' => ['required' => false, 'locked' => true, 'auto_calculated' => true, 'disabled' => false, 'optional' => false],
                    'plate_code' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'plate_number' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'rta_plate_category' => ['required' => true, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                ]);
                break;

            case self::RTA_VEHICLE_RENEWAL_WITH_CHANGE_NUMBER:
                $config = array_merge($config, [
                    'policy_effective_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'policy_expiry_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'certificate_start_date' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'certificate_end_date' => ['required' => false, 'locked' => true, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'plate_code' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'plate_number' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                    'rta_plate_category' => ['required' => false, 'locked' => false, 'auto_calculated' => false, 'disabled' => false, 'optional' => false],
                ]);
                break;
        }

        return $config;
    }

    /**
     * Calculate auto-calculated dates based on RTA transaction type
     */
    // public function calculateDates(string $rtaTransactionType, array $inputData, bool $isGigRenewal = false, array $previousPolicyData = []): array
    // {
    //     $calculatedDates = [];

    //     switch ($rtaTransactionType) {
    //         case self::RTA_NEW_VEHICLE_REGISTRATION:
    //         case self::RTA_CHANGE_VEHICLE_OWNERSHIP:
    //             if (! empty($inputData['policy_effective_date'])) {
    //                 $policyEffectiveDate = Carbon::parse($inputData['policy_effective_date']);
    //                 $calculatedDates['policy_expiry_date'] = $policyEffectiveDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
    //                 $calculatedDates['certificate_start_date'] = $policyEffectiveDate->format('Y-m-d');
    //                 $calculatedDates['certificate_end_date'] = $calculatedDates['policy_expiry_date'];
    //             }
    //             break;

    //         case self::RTA_VEHICLE_RENEWAL:
    //             if ($isGigRenewal) {
    //                 // For GIG renewal, policy effective date comes from previous policy + 1 day
    //                 if (! empty($previousPolicyData['policy_expiry_date'])) {
    //                     $previousExpiryDate = Carbon::parse($previousPolicyData['policy_expiry_date']);
    //                     $calculatedDates['policy_effective_date'] = $previousExpiryDate->addDay()->format('Y-m-d');
    //                 }

    //                 // Policy expiry date comes from eBao (would be set elsewhere)
    //                 if (! empty($previousPolicyData['policy_expiry_date_from_ebao'])) {
    //                     $calculatedDates['policy_expiry_date'] = $previousPolicyData['policy_expiry_date_from_ebao'];
    //                 }

    //                 // Certificate end date is 13 months from certificate start date
    //                 if (! empty($inputData['certificate_start_date'])) {
    //                     $certificateStartDate = Carbon::parse($inputData['certificate_start_date']);
    //                     $calculatedDates['certificate_end_date'] = $certificateStartDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
    //                 }
    //             } else {
    //                 // For non-GIG renewal
    //                 if (! empty($inputData['policy_effective_date'])) {
    //                     $policyEffectiveDate = Carbon::parse($inputData['policy_effective_date']);
    //                     $calculatedDates['certificate_start_date'] = $policyEffectiveDate->format('Y-m-d');
    //                     $calculatedDates['certificate_end_date'] = $policyEffectiveDate->copy()->addMonths(self::POLICY_DURATION_MONTHS)->format('Y-m-d');
    //                     $calculatedDates['policy_expiry_date'] = $calculatedDates['certificate_end_date'];
    //                 }
    //             }
    //             break;
    //     }

    //     return $calculatedDates;
    // }

    /**
     * Process form data based on RTA transaction type rules
     */
    // public function processFormData(string $rtaTransactionType, array $inputData, bool $isGigRenewal = false, array $previousPolicyData = []): array
    // {
    //     $processedData = $inputData;

    //     // Get field configuration
    //     $fieldConfig = $this->getFieldConfiguration($rtaTransactionType, $isGigRenewal);

    //     // Remove locked/auto-calculated fields from input data
    //     foreach ($fieldConfig as $fieldName => $config) {
    //         if ($config['locked'] && $config['auto_calculated']) {
    //             unset($processedData[$fieldName]);
    //         }
    //     }

    //     // Calculate and set auto-calculated dates
    //     $calculatedDates = $this->calculateDates($rtaTransactionType, $inputData, $isGigRenewal, $previousPolicyData);
    //     $processedData = array_merge($processedData, $calculatedDates);

    //     return $processedData;
    // }

    /**
     * Get frontend field configuration for UI rendering
     */
    public function getFrontendFieldConfig(string $rtaTransactionType, bool $isGigRenewal = false): array
    {
        $fieldConfig = $this->getFieldConfiguration($rtaTransactionType, $isGigRenewal);

        $frontendConfig = [];
        foreach ($fieldConfig as $fieldName => $config) {
            $frontendConfig[$fieldName] = [
                'disabled' => ($config['locked'] ?? false) || ($config['disabled'] ?? false),
                'required' => $config['required'] ?? false,
                'readonly' => $config['locked'] ?? false,
                'hidden' => $config['disabled'] ?? false,
                'optional' => $config['optional'] ?? false,
                'auto_calculated' => $config['auto_calculated'] ?? false,
            ];
        }

        return $frontendConfig;
    }

    /**
     * Determine if renewal is GIG renewal based on previous policy data
     */
    // public function isGigRenewal(array $previousPolicyData): bool
    // {
    //     return ! empty($previousPolicyData['insurance_provider_code']) &&
    //            $previousPolicyData['insurance_provider_code'] === self::GIG_PROVIDER_CODE;
    // }

    /**
     * Get validation rules summary for a specific RTA transaction type
     */
    public function getValidationSummary(string $rtaTransactionType, bool $isGigRenewal = false): array
    {
        $summary = [];

        switch ($rtaTransactionType) {
            case self::RTA_NEW_VEHICLE_REGISTRATION:
                $summary = [
                    'transaction_name' => 'New Vehicle Registration',
                    'policy_effective_date' => 'Selectable (max 30 days from today)',
                    'policy_expiry_date' => 'Auto-calculated (Policy Effective Date + 13 months)',
                    'certificate_start_date' => 'Auto-set to Policy Effective Date',
                    'certificate_end_date' => 'Auto-set to Policy Expiry Date',
                    'plate_code' => 'Disabled (not required)',
                    'plate_number' => 'Disabled (not required)',
                    'rta_plate_category' => 'Optional',
                ];
                break;

            case self::RTA_VEHICLE_RENEWAL:
                if ($isGigRenewal) {
                    $summary = [
                        'transaction_name' => 'Vehicle Renewal (GIG)',
                        'policy_effective_date' => 'Auto-calculated (Previous Policy Expiry + 1 day)',
                        'policy_expiry_date' => 'Auto-set from eBao',
                        'certificate_start_date' => 'Selectable (no backdating, ≤ Policy Effective Date)',
                        'certificate_end_date' => 'Auto-calculated (Certificate Start + 13 months)',
                        'plate_code' => 'Required',
                        'plate_number' => 'Required',
                        'rta_plate_category' => 'Required',
                    ];
                } else {
                    $summary = [
                        'transaction_name' => 'Vehicle Renewal (Non-GIG)',
                        'policy_effective_date' => 'Selectable (max 30 days from today)',
                        'policy_expiry_date' => 'Auto-set to Certificate End Date',
                        'certificate_start_date' => 'Auto-set to Policy Effective Date',
                        'certificate_end_date' => 'Auto-calculated (Certificate Start + 13 months)',
                        'plate_code' => 'Required',
                        'plate_number' => 'Required',
                        'rta_plate_category' => 'Required',
                    ];
                }
                break;

            case self::RTA_CHANGE_VEHICLE_OWNERSHIP:
                $summary = [
                    'transaction_name' => 'Change Vehicle Ownership',
                    'policy_effective_date' => 'Selectable (max 30 days from today)',
                    'policy_expiry_date' => 'Auto-calculated (Policy Effective Date + 13 months)',
                    'certificate_start_date' => 'Auto-set to Policy Effective Date',
                    'certificate_end_date' => 'Auto-set to Policy Expiry Date',
                    'plate_code' => 'Required',
                    'plate_number' => 'Required',
                    'rta_plate_category' => 'Required',
                ];
                break;
        }

        return $summary;
    }
}
