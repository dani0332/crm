<?php

namespace App\Services\AML;

use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteType;
use App\Repositories\CarQuoteRepository;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;

class AMLEntityService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private readonly AMLService $amlService
    ) {}

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
     *
     * @return array{status: bool, response: mixed, message: string}
     */
    public function linkEntityToQuote(int $quoteTypeId, int $quoteRequestId, int $entityId, ?string $triggeredFrom = null): array
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $quoteRequestId);

        LoggerService::startQuoteLogging($quoteObject);
        LoggerService::info('Link Entity to Quote Process Start');

        // Get insured entity
        $insured = Insured::where('id', $entityId)->first();

        if (! $insured) {
            LoggerService::info('Link Entity to Quote Process - Entity not found');

            return [
                'status' => false,
                'response' => null,
                'message' => 'Entity not found',
            ];
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
            LoggerService::info('Updating car quote company details');
            $this->updateCarQuoteCompanyDetails($quoteRequestId, $entity);
        }

        return [
            'status' => true,
            'response' => $entity,
            'message' => 'Entity Linked Successfully',
        ];
    }

    private function linkCustomerInsured(int $quoteTypeId, int $quoteRequestId, int $customerId, int $insuredId): void
    {
        $customerInsured = CustomerInsured::where('customer_id', $customerId)
            ->where('insured_id', $insuredId)
            ->whereNull('quote_type_id')
            ->whereNull('quote_request_id')
            ->first();

        if ($customerInsured) {
            LoggerService::info('Customer Insured found against orphaned record');
            $customerInsured->update([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
                'updated_at' => now(),
            ]);
        } else {
            LoggerService::info('Customer Insured not found against orphaned record, creating new one');
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
     * Reminder:: Remove when new structure is completely mapped
     */
    private function handleLegacyEntityStructure(int $quoteTypeId, int $quoteRequestId, Insured $insured, ?string $triggeredFrom): Entity
    {
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
        LoggerService::info('Updating or creating entity mapping', extra: [
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
            'update_fields' => $updateFields,
        ]);

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

    private function cleanupPreviousEntity(?QuoteRequestEntityMapping $existingEntityMapping): void
    {
        if (! $existingEntityMapping) {
            LoggerService::info('No existing entity mapping found');

            return;
        }

        $previousEntity = $existingEntityMapping->entity;
        $entityMappingCount = QuoteRequestEntityMapping::where([
            'entity_id' => $previousEntity->id ?? null,
        ])->count();

        // Delete if no mappings exist and no trade license (Jawad's change for car commercial quote)
        if ($entityMappingCount === 0 && empty($previousEntity->trade_license_no)) {
            LoggerService::info('Deleting previous entity because no mappings exist and no trade license');
            $previousEntity->delete();
        }
    }

    private function updateCarQuoteCompanyDetails(int $quoteRequestId, Entity $entity): void
    {
        CarQuoteRepository::where('id', $quoteRequestId)->update([
            'company_name' => $entity->company_name,
            'company_address' => $entity->company_address,
        ]);
    }

    /**
     * Get entity details by quote type ID and quote request ID
     * Used in AML quote details page to retrieve entity mapping
     * Reminder:: This will be removed when customer members mapping is updated with insured id
     */
    public function getEntityDetailsByQuote(int $quoteTypeId, int $quoteRequestId)
    {
        return QuoteRequestEntityMapping::with(['entity', 'entity.quoteMember'])
            ->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteRequestId])
            ->first() ?? [];
    }
}
