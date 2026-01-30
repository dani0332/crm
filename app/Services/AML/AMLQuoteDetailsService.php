<?php

namespace App\Services\AML;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLStatusCode;
use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericModelTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\Emirate;
use App\Models\Payment;
use App\Models\QuoteType;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\NationalityRepository;
use App\Services\AML\DTOs\AMLPageData;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsurancePayloadMapping;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

/**
 * Service for preparing AML Quote Details page data
 * Handles all business logic for the AML screening/detail page
 */
class AMLQuoteDetailsService
{
    public function __construct(
        private readonly AMLService $amlService,
        private readonly AMLBusinessPayloadService $businessPayloadService,
        private readonly AMLLookupsService $lookupsService,
        private readonly AMLInsuredService $insuredService,
        private readonly AMLEntityService $entityService
    ) {}

    /**
     * Prepare AML quote details data for display
     */
    public function prepareQuoteDetailsData(int $quoteTypeId, int $quoteRequestId): AMLPageData
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);

        LoggerService::startQuoteLogging($quoteRequest);
        LoggerService::info('AML Details Page - Prepare Quote Details Data');

        // Add quote link to quote request
        $this->addQuoteLinkToRequest($quoteRequest, $quoteType);

        // Get KYC logs and check for escalations
        $kycLogs = $this->amlService->getKYCLogs($quoteTypeId, $quoteRequestId);
        $isAnyEscalated = $this->countEscalatedLogs($kycLogs);

        // Get insurance provider information
        $insuranceProvider = $quoteRequest?->plan?->insuranceProvider;
        $providerCode = $insuranceProvider?->code ?? '';

        // Get lookups (with additional fields if enabled)
        $lookups = $this->lookupsService->getLookupsForQuote(
            $quoteType,
            $insuranceProvider,
            $quoteRequest
        );

        // Get insured and entity details
        $insuredDetails = $this->insuredService->getInsuredDetailsByQuote(
            $quoteRequest->customer_id,
            $quoteTypeId,
            $quoteRequestId
        );
        $entityDetails = $this->entityService->getEntityDetailsByQuote($quoteTypeId, $quoteRequestId);

        // Get reference data
        $nationalities = NationalityRepository::withActive()->get();
        $emirates = Emirate::where('is_active', 1)->orderBy('sort_order')->get();

        // Get member details
        $membersDetails = CustomerMembersRepository::getBy($quoteRequest->id, $quoteType->code);
        $uboDetails = CustomerMembersRepository::getBy(
            $quoteRequest->id,
            $quoteType->code,
            CustomerTypeEnum::Entity
        );

        // Get payment and card holder information
        $cardHolderName = $this->getCardHolderName($quoteRequest->code);

        // Get AML status information
        $amlStatusName = AMLStatusCode::getName($quoteRequest->aml_status);
        $screeningType = AMLService::getKycType($quoteTypeId, $quoteRequestId);
        $quoteAmlStatus = $this->getQuoteAmlStatus($quoteRequest->aml_status);

        // Get insurer-specific configuration
        $gigInsurerDefaultEmail = $this->getInsurerDefaultEmail($providerCode);
        $isInsurerSyncEnabled = $this->amlService->isInsurerSyncEnabled($quoteType, $quoteRequest);
        $isPolicyAutomationEnabled = $this->isPolicyAutomationEnabled($quoteType, $insuranceProvider);

        // Get additional fields configuration
        $isAddionalFieldsEnabled = $this->amlService->isAdditionalVehicleAndDriverDetailsEnabled(
            $quoteType?->code,
            $insuranceProvider?->code,
            $quoteRequest?->registration_type
        );

        // Get business-specific payload
        $businessPayload = $this->businessPayloadService->getBusinessPayload($quoteType, $quoteRequest);

        // Get RTA configuration for Car quotes
        $rtaConfigurationData = $this->amlService->getRTATransactionConfigurations($quoteType->code);

        // Prepare enums
        $enums = $this->prepareEnums();

        return new AMLPageData(array_merge([
            'quoteType' => $quoteType,
            'quoteRequest' => $quoteRequest,
            'amlStatusName' => $amlStatusName,
            'kycLogs' => $kycLogs,
            'lookups' => $lookups,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'insuredDetails' => $insuredDetails,
            'entityDetails' => $entityDetails,
            'membersDetails' => $membersDetails,
            'uboDetails' => $uboDetails,
            'cardHolderName' => $cardHolderName,
            'quoteAmlStatus' => $quoteAmlStatus,
            'screeningType' => $screeningType,
            'gigInsurerDefaultEmail' => $gigInsurerDefaultEmail,
            'isAnyEscalated' => $isAnyEscalated,
            'isInsurerSyncEnabled' => $isInsurerSyncEnabled,
            'isAddionalFieldsEnabled' => $isAddionalFieldsEnabled,
            'isPrivateCar' => $quoteRequest?->registration_type === CarRegistrationType::PERSONAL,
            'LIVAEnums' => app(LivaInsurancePayloadMapping::class)->rtaTransactionTypeEnum(),
            'insurerName' => InsuranceProvidersEnum::getTextByCode($providerCode),
            'isPolicyAutomationEnabled' => $isPolicyAutomationEnabled,
            ...$enums,
        ], $businessPayload, $rtaConfigurationData));
    }

    /**
     * Add quote link to quote request object
     */
    private function addQuoteLinkToRequest(object $quoteRequest, QuoteType $quoteType): void
    {
        $quoteRequest->quote_link = checkPersonalQuotes($quoteType->code)
            ? '/personal-quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid
            : '/quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid;
    }

    /**
     * Count escalated logs
     *
     * @param  \Illuminate\Support\Collection  $kycLogs
     */
    private function countEscalatedLogs($kycLogs): int
    {
        if ($kycLogs->isEmpty()) {
            return 0;
        }

        return $kycLogs->filter(function ($log) {
            return $log['decision'] == AMLDecisionStatusEnum::ESCALATED;
        })->count();
    }

    /**
     * Get card holder name from payment
     */
    private function getCardHolderName(string $code): string
    {
        $payment = Payment::where('code', $code)
            ->with(['getCustomerPaymentInstrument' => fn ($query) => $query->whereNotNull('card_holder_name')])
            ->first();

        return $payment?->getCustomerPaymentInstrument?->card_holder_name ?? '';
    }

    /**
     * Get quote AML status
     */
    private function getQuoteAmlStatus(?string $amlStatusCode): ?int
    {
        if ($amlStatusCode === null) {
            return null;
        }

        $checkScreeningStatus = [
            AMLStatusCode::AMLScreeningCleared => AMLStatusCode::AMLScreeningClearedInt,
            AMLStatusCode::AMLScreeningFailed => AMLStatusCode::AMLScreeningFailedInt,
        ];

        return $checkScreeningStatus[$amlStatusCode] ?? null;
    }

    /**
     * Get insurer default email based on provider code
     */
    private function getInsurerDefaultEmail(string $providerCode): string
    {
        $isLIVA = $providerCode == InsuranceProvidersEnum::RSA;

        return $isLIVA
            ? GenericModelTypeEnum::LIVA_INSURER_SCREENIN_DEFAULT_EMAIL
            : GenericModelTypeEnum::GIG_INSURER_SCREENIN_DEFAULT_EMAIL;
    }

    /**
     * Check if policy automation is enabled for the insurer
     */
    private function isPolicyAutomationEnabled(QuoteType $quoteType, ?object $insuranceProvider): bool
    {
        if ($quoteType->code !== quoteTypeCode::Car || ! $insuranceProvider) {
            return false;
        }

        $policyIssuanceService = app(PolicyIssuanceService::class)->init(
            $quoteType->code,
            $insuranceProvider->code
        );

        return $policyIssuanceService?->isPolicyIssuanceAutomationEnabled() ?? false;
    }

    /**
     * Prepare enum arrays for view
     */
    private function prepareEnums(): array
    {
        return [
            'amlStatusCode' => AMLStatusCode::asArray(),
            'amlDecisionStatusEnum' => AMLDecisionStatusEnum::asArray(),
            'quoteTypeIdEnum' => QuoteTypeId::asArray(),
            'quoteStatusEnums' => QuoteStatusEnum::asArray(),
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'amlStatusEvaluation' => AMLDecisionStatusEnum::amlStatusEvaluation(),
            'defaultNationality' => GenericRequestEnum::DEFAULT_NATIONALITY,
            'permissionsEnum' => PermissionsEnum::asArray(),
        ];
    }
}
