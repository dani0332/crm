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
use App\Enums\QuoteTypeId;
use App\Models\Emirate;
use App\Models\Payment;
use App\Models\QuoteType;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\NationalityRepository;
use App\Services\AMLService;
use App\Services\CentralService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsurancePayloadMapping;

class AMLQuoteDetailsService
{
    public function __construct(
        private readonly AMLService $amlService,
        private readonly AMLQueryService $queryService,
        private readonly AMLInsurerService $insurerService,
        private readonly AMLBusinessPayloadService $businessPayloadService,
        private readonly AMLLookupsService $lookupsService,
        private readonly AMLInsuredService $insuredService,
        private readonly AMLEntityService $entityService
    ) {}

    public function prepareQuoteDetailsData(int $quoteTypeId, int $quoteRequestId): array
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);

        // Add quote link to quote request
        $this->addQuoteLinkToRequest($quoteRequest, $quoteType);

        // Get AML logs and check for escalations
        $amlLogs = $this->queryService->getAMLLogs($quoteTypeId, $quoteRequestId);
        $isAnyEscalated = $this->countEscalatedLogs($amlLogs);

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
        $emirates = Emirate::withActive()->orderBy('text', 'asc')->get();

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
        $screeningType = $this->queryService->getAMLScreeningType($quoteTypeId, $quoteRequestId);
        $quoteAmlStatus = $this->getQuoteAmlStatus($quoteRequest->aml_status);

        // Get insurer-specific configuration
        $gigInsurerDefaultEmail = $this->getInsurerDefaultEmail($providerCode);
        $isInsurerSyncEnabled = $this->insurerService->isInsurerSyncEnabled($quoteType, $quoteRequest);
        $isPolicyAutomationEnabled = $this->amlService->isPolicyAutomationEnabled($quoteType->code, $insuranceProvider?->code);

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

        $isEmirateOfRegistrationLocked = app(CentralService::class)->isEmirateOfRegistrationLocked(
            $quoteRequest,
            $quoteType->code
        );

        // Prepare enums
        $enums = $this->prepareEnums();

        return array_merge([
            'quoteType' => $quoteType,
            'isEmirateOfRegistrationLocked' => $isEmirateOfRegistrationLocked,
            'quoteRequest' => $quoteRequest,
            'amlStatusName' => $amlStatusName,
            'amlLogs' => $amlLogs,
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
        ], $businessPayload, $rtaConfigurationData);
    }

    private function addQuoteLinkToRequest(object $quoteRequest, QuoteType $quoteType): void
    {
        $quoteRequest->quote_link = checkPersonalQuotes($quoteType->code)
            ? '/personal-quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid
            : '/quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid;
    }

    private function countEscalatedLogs($amlLogs): int
    {
        if ($amlLogs->isEmpty()) {
            return 0;
        }

        return $amlLogs->filter(function ($log) {
            return $log['decision'] == AMLDecisionStatusEnum::ESCALATED;
        })->count();
    }

    private function getCardHolderName(string $code): string
    {
        $payment = Payment::where('code', $code)
            ->with(['getCustomerPaymentInstrument' => fn ($query) => $query->whereNotNull('card_holder_name')])
            ->first();

        return $payment?->getCustomerPaymentInstrument?->card_holder_name ?? '';
    }

    private function getQuoteAmlStatus(?string $amlStatusCode): ?int
    {
        if ($amlStatusCode === null) {
            return null;
        }

        $checkScreeningStatus = [
            AMLStatusCode::AMLScreeningCleared => AMLStatusCode::AML_SCREENING_CLEARED_ID,
            AMLStatusCode::AMLScreeningFailed => AMLStatusCode::AML_SCREENING_FAILED_ID,
        ];

        return $checkScreeningStatus[$amlStatusCode] ?? null;
    }

    private function getInsurerDefaultEmail(string $providerCode): string
    {
        $isLIVA = $providerCode == InsuranceProvidersEnum::RSA;

        return $isLIVA
            ? GenericModelTypeEnum::LIVA_INSURER_SCREENIN_DEFAULT_EMAIL
            : GenericModelTypeEnum::GIG_INSURER_SCREENIN_DEFAULT_EMAIL;
    }

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
