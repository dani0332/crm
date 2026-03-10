<?php

namespace App\Services\AML;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\Kyc;
use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\QuoteRequestEntityMapping;
use App\Repositories\CarQuoteRepository;
use App\Services\AMLService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
            'id_type' => GenericRequestEnum::TRADE_LICENSE,
            'id_number' => $tradeLicense,
        ])->first();
    }

    public function linkEntityToQuote(int $quoteTypeId, int $quoteRequestId, int $entityId, ?string $triggeredFrom = null): array
    {
        $quoteTypeCode = QuoteTypes::getName($quoteTypeId)?->value;
        if (! $quoteTypeCode) {
            return [
                'status' => false,
                'response' => null,
                'message' => 'Invalid quote type',
            ];
        }
        $quoteObject = $this->getQuoteObject($quoteTypeCode, $quoteRequestId);

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

        try {
            $result = DB::transaction(function () use ($quoteTypeId, $quoteRequestId, $quoteObject, $insured, $triggeredFrom) {
                // Update insured in personal quote (new structure)
                $this->amlService->updateInsuredInPersonalQuote($quoteTypeId, $quoteObject, $insured);

                // Link customer to insured
                $this->linkCustomerInsured($quoteTypeId, $quoteRequestId, $quoteObject, $insured->id);

                // Handle legacy structure migration
                $entity = $this->handleLegacyEntityStructure(
                    $quoteTypeId,
                    $quoteRequestId,
                    $insured,
                    $triggeredFrom
                );

                if (! $entity) {
                    throw new RuntimeException('Entity not found for the provided trade license number');
                }

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
            });
        } catch (RuntimeException $e) {
            $result = [
                'status' => false,
                'response' => null,
                'message' => $e->getMessage(),
            ];
        }

        return $result;
    }

    private function linkCustomerInsured(int $quoteTypeId, int $quoteRequestId, $quoteObject, int $insuredId): void
    {
        $isCustomerInsuredAssociationUpdated = false;
        $customerId = $quoteObject->customer_id;
        $orphanedRecord = CustomerInsured::where('customer_id', $customerId)
            ->where('insured_id', $insuredId)
            ->whereNull('quote_type_id')
            ->whereNull('quote_request_id')
            ->lockForUpdate()
            ->first();

        if ($orphanedRecord) {
            LoggerService::info('Customer Insured found against orphaned record');

            $isCustomerInsuredAssociationUpdated = true;
            CustomerInsured::forQuote($quoteTypeId, $quoteRequestId)
                ->update(['is_active' => false]);

            $orphanedRecord->update([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
                'is_active' => true,
                'updated_at' => now(),
            ]);

            LoggerService::info('Updated orphaned customer_insured record', extra: [
                'customer_insured_id' => $orphanedRecord->id,
                'customer_id' => $customerId,
                'insured_id' => $insuredId,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
            ]);
        }

        if (! $isCustomerInsuredAssociationUpdated) {
            LoggerService::info('Customer Insured not found against orphaned record');
            $existingQuoteMapping = CustomerInsured::active()->forQuote($quoteTypeId, $quoteRequestId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if ($existingQuoteMapping && $existingQuoteMapping->insured_id !== $insuredId) {
                $isCustomerInsuredAssociationUpdated = true;

                CustomerInsured::createOrUpdateActive([
                    'customer_id' => $customerId,
                    'insured_id' => $insuredId,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quoteRequestId,
                ], [], true);

                $quoteObject->update(['kyc_decision' => Kyc::PENDING]);

                LoggerService::info('Insured association changed for quote', extra: [
                    'old_insured_id' => $existingQuoteMapping->insured_id,
                    'new_insured_id' => $insuredId,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quoteRequestId,
                ]);
            } elseif (! $existingQuoteMapping) {
                // This is a completely new quote-insured association
                $isCustomerInsuredAssociationUpdated = true;

                CustomerInsured::createOrUpdateActive([
                    'customer_id' => $customerId,
                    'insured_id' => $insuredId,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quoteRequestId,
                ], [], true);

                LoggerService::info('New insured association created for quote', extra: [
                    'insured_id' => $insuredId,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $quoteRequestId,
                ]);
            }
        }
    }

    /**
     * Handle legacy entity structure migration
     * Reminder:: Remove when new structure is completely mapped
     */
    private function handleLegacyEntityStructure(int $quoteTypeId, int $quoteRequestId, Insured $insured, ?string $triggeredFrom): ?Entity
    {
        // Find old structure entity by id_number (trade license)
        $oldStructureEntity = Entity::where('trade_license_no', $insured->id_number)->first();

        // Check if entity exists
        if (! $oldStructureEntity) {
            LoggerService::warning('Entity not found for trade license', extra: [
                'id_type' => $insured->id_type,
                'id_number' => $insured->id_number,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
            ]);

            return null;
        }

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
