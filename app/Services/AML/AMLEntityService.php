<?php

namespace App\Services\AML;

use App\Enums\CustomerTypeEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteType;
use App\Repositories\CarQuoteRepository;
use App\Services\AML\DTOs\EntityLinkResult;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;

/**
 * Service for handling AML entity operations
 * Manages entity lookup, linking, and legacy structure migration
 */
class AMLEntityService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly AMLService $amlService
    ) {}

    /**
     * Fetch entity by trade license number
     */
    public function fetchEntityByTradeLicense(string $tradeLicense): ?Insured
    {
        return Insured::where([
            'customer_type' => CustomerTypeEnum::Entity,
            'trade_license_no' => $tradeLicense,
        ])->first();
    }

    /**
     * Link entity details to a quote
     * Handles both new structure and legacy structure migration
     */
    public function linkEntityToQuote(
        int $quoteTypeId,
        int $quoteRequestId,
        int $entityId,
        ?string $triggeredFrom = null
    ): EntityLinkResult {
        $quoteType = QuoteType::where('id', $quoteTypeId)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $quoteRequestId);

        LoggerService::startQuoteLogging($quoteObject, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        // Get insured entity
        $insured = Insured::where('id', $entityId)->first();

        if (! $insured) {
            return new EntityLinkResult(
                status: false,
                entity: null,
                message: 'Entity not found'
            );
        }

        // Update insured in personal quote (new structure)
        $this->amlService->updateInsuredInPersonalQuote($quoteTypeId, $quoteObject, $insured);

        // Link customer to insured
        $this->linkCustomerInsured($quoteTypeId, $quoteRequestId, $quoteObject->customer_id, $insured->id);

        // Handle legacy structure migration
        $entity = $this->handleLegacyEntityStructure(
            $quoteTypeId,
            $quoteRequestId,
            $insured,
            $triggeredFrom
        );

        // Update car quote if applicable
        if ($quoteTypeId == QuoteTypeId::Car) {
            $this->updateCarQuoteCompanyDetails($quoteRequestId, $entity);
        }

        return new EntityLinkResult(
            status: true,
            entity: $entity,
            message: 'Entity Linked Successfully'
        );
    }

    /**
     * Link customer to insured
     */
    private function linkCustomerInsured(
        int $quoteTypeId,
        int $quoteRequestId,
        int $customerId,
        int $insuredId
    ): void {
        $customerInsured = CustomerInsured::where('customer_id', $customerId)
            ->where('insured_id', $insuredId)
            ->whereNull('quote_type_id')
            ->whereNull('quote_request_id')
            ->first();

        if ($customerInsured) {
            $customerInsured->update([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
                'updated_at' => now(),
            ]);
        } else {
            CustomerInsured::updateOrCreate([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
            ], [
                'customer_id' => $customerId,
                'insured_id' => $insuredId,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Handle legacy entity structure migration
     * TODO: Remove when new structure is completely mapped
     */
    private function handleLegacyEntityStructure(
        int $quoteTypeId,
        int $quoteRequestId,
        Insured $insured,
        ?string $triggeredFrom
    ): Entity {
        // Find old structure entity
        $oldStructureEntity = Entity::where('trade_license_no', $insured->trade_license_no)->first();
        $existingEntityMapping = QuoteRequestEntityMapping::where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
        ])->first();

        // Prepare update fields
        $updateFields = [
            'entity_id' => $oldStructureEntity->id,
            'entity_type_code' => $triggeredFrom ? LookupsEnum::SUB_ENTITY : LookupsEnum::PARENT_ENTITY,
        ];

        // Update or create entity mapping
        QuoteRequestEntityMapping::updateOrCreate(
            ['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteRequestId],
            $updateFields
        );

        // Load entity with relationships
        $entity = Entity::with([
            'quoteRequestEntityMapping' => function ($mappedEntity) use ($quoteTypeId, $quoteRequestId) {
                $mappedEntity->where([
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quoteRequestId,
                ]);
            },
            'quoteMember',
        ])->where('id', $oldStructureEntity->id)->first();

        // Clean up previous entity if needed
        $this->cleanupPreviousEntity($existingEntityMapping);

        return $entity;
    }

    /**
     * Clean up previous entity if no longer referenced
     */
    private function cleanupPreviousEntity(?QuoteRequestEntityMapping $existingEntityMapping): void
    {
        if (! $existingEntityMapping) {
            return;
        }

        $previousEntity = $existingEntityMapping->entity;
        $entityMappingCount = QuoteRequestEntityMapping::where([
            'entity_id' => $previousEntity->id ?? null,
        ])->count();

        // Delete if no mappings exist and no trade license (Jawad's change for car commercial quote)
        if ($entityMappingCount === 0 && empty($previousEntity->trade_license_no)) {
            $previousEntity->delete();
        }
    }

    /**
     * Update car quote company details
     */
    private function updateCarQuoteCompanyDetails(int $quoteRequestId, Entity $entity): void
    {
        CarQuoteRepository::where('id', $quoteRequestId)->update([
            'company_name' => $entity->company_name,
            'company_address' => $entity->company_address,
        ]);
    }
}
