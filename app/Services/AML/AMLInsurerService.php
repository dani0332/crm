<?php

declare(strict_types=1);

namespace App\Services\AML;

use App\Enums\AMLScreeningTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Models\KycLog;
use App\Models\QuoteType;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\GIGInsuranceService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsuranceService;
use App\Traits\GenericQueriesAllLobs;

class AMLInsurerService
{
    use GenericQueriesAllLobs;

    public function __construct() {}

    public function isInsurerSyncEnabled(QuoteType $quoteType, object $quote): bool
    {
        $insurerScreenType = [
            InsuranceProvidersEnum::AXA => AMLScreeningTypeEnum::INSURER_AXA,
            InsuranceProvidersEnum::RSA => AMLScreeningTypeEnum::INSURER_RSA,
        ];

        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType->text);

        if (! in_array($insuranceProvider?->code, array_keys($insurerScreenType))) {
            return false;
        }

        if (
            $insuranceProvider?->code === InsuranceProvidersEnum::RSA &&
            auth()->user()->can(PermissionsEnum::EDIT_VEHICLE_TRANSACTION_DRIVER_DETAILS)
        ) {
            return true;
        }

        return $this->checkKycLogsForSyncEligibility(
            $quote->id,
            $quoteType->id,
            $insurerScreenType[$insuranceProvider->code]
        );
    }

    private function checkKycLogsForSyncEligibility(int $quoteRequestId, int $quoteTypeId, string $screeningType): bool
    {
        $kycLogs = KycLog::withTrashed()
            ->where([
                'quote_request_id' => $quoteRequestId,
                'quote_type_id' => $quoteTypeId,
            ])
            ->where('screening_type', $screeningType)
            ->latest()
            ->first();

        if (! $kycLogs || is_null($kycLogs->results)) {
            return false;
        }

        $screeningResult = json_decode($kycLogs->results);

        if (! isset($screeningResult->uwApprovalStatus, $screeningResult->quoteStatus)) {
            return false;
        }

        // Sync is enabled when UW approval is "No" and quote status matches EBAO status
        return $screeningResult->uwApprovalStatus === GenericRequestEnum::EBAO_UW_APPROVAL_STATUS_NO
            && $screeningResult->quoteStatus === GenericRequestEnum::EBAO_QUOTE_STATUS;
    }

    public function getQuoteDetailsFromInsurer(int $quoteTypeId, string $quoteUID): array
    {
        try {
            $quoteType = QuoteTypes::getName($quoteTypeId)->value;
            $quoteDetails = $this->getQuoteObjectBy($quoteType, $quoteUID, 'uuid');
            $insurerCode = getInsuranceProvider($quoteDetails->payments()->mainLeadPayment()->first(), $quoteType);

            return match (ucfirst($quoteType)) {
                QuoteTypes::CAR->value => match ($insurerCode->code) {
                    InsuranceProvidersEnum::RSA => app(LivaInsuranceService::class)->getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails),
                    InsuranceProvidersEnum::AXA => app(GIGInsuranceService::class)->getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails),

                    default => [
                        'success' => false,
                        'message' => 'Insurer not supported for quote type: '.$quoteType,
                        'data' => null,
                    ],
                },
                default => [
                    'success' => false,
                    'message' => 'Quote type not supported: '.$quoteType,
                    'data' => null,
                ],
            };
        } catch (\Exception $e) {
            LoggerService::info('Get Quote Details From Insurer - Exception: '.$e->getMessage().' - QuoteUID: '.$quoteUID);

            return [
                'success' => false,
                'message' => 'Exception occurred: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }
}
