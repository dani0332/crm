<?php

namespace App\Console\Commands;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\QuoteRequestEntityMapping;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Entities Insured Migration Command
 * This command migrates data from the legacy Entity structure to the new Insured table structure.
 * It handles the migration of entities, their KYC details, and creates proper mappings to customers.
 * This command is deprecated and should be removed after the migration is completed.
 */
class EntitiesInsuredMigration extends Command
{
    protected $signature = 'migrate:entities-insured';
    protected $description = 'Migrate Entity data to the Insured table structure, this command is deprecated and should be removed after the migration is completed';
    protected $genericQueriesAllLobs;

    public function __construct()
    {
        parent::__construct();
        $this->genericQueriesAllLobs = new class
        {
            use GenericQueriesAllLobs;
        };
    }

    public function handle()
    {
        info('------------------- Entities Insured Migration Command Started At: '.now().' -------------------');

        info('Command:EntitiesInsuredMigration - Start updating the existing records in the insured table by setting the customer type to Individual at '.now());
        $insured = Insured::where(function ($query) {
            $query->whereNull('customer_type')
                ->orWhere('customer_type', '');
        })->where('entity_id', null);

        if ($insured->count() > 0) {
            $insured->update(['customer_type' => CustomerTypeEnum::Individual]);
        }

        info('Command:EntitiesInsuredMigration - Existing insured table records updated as Individual successfully at '.now());

        $failedDetails = [];
        $migratedCount = $skippedCount = $failedCount = 0;

        Entity::chunk(500, function ($entities) use (&$migratedCount, &$skippedCount, &$failedCount, &$failedDetails) {
            foreach ($entities as $entity) {
                $isEntityAlreadyCreated = Insured::where('entity_id', $entity->id)->first();
                if ($isEntityAlreadyCreated) {
                    info('Command:EntitiesInsuredMigration - Entity '.$entity->id.' already exists in the insured table');
                    $skippedCount++;

                    continue;
                }

                info('*********************** Migrating entity details against ID: '.$entity->id.' **********************');
                info('Creating new insured record for entity '.$entity->id);

                try {
                    DB::beginTransaction();

                    $insured = Insured::create([
                        'customer_type' => CustomerTypeEnum::Entity,
                        'first_name' => $entity->kyc_first_name ?? null,
                        'last_name' => $entity->kyc_last_name ?? null,
                        'id_type' => $entity->id_type ?? null,
                        'id_number' => $entity->id_number ?? null,
                        'code' => $entity->code,
                        'trade_license_no' => $entity->trade_license_no,
                        'company_name' => $entity->company_name,
                        'company_address' => $entity->company_address ?? null,
                        'industry_type_code' => $entity->industry_type_code ?? null,
                        'emirate_of_registration_id' => $entity->emirate_of_registration_id ?? null,
                        'sage_customer_number' => $entity->sage_customer_number ?? null,
                        'entity_id' => $entity->id,
                    ]);

                    $this->createCustomerInsuredMappingsForEntity($insured, $entity);

                    DB::commit();
                    $migratedCount++;
                    info('*********************** Migrated entity details against ID: '.$entity->id.' **********************');
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedCount++;
                    $failedDetails['entity_id'][] = $entity->id;
                    $failedDetails['message'][] = 'failed to migrate entity. message: '.$e->getMessage();
                    info("Failed to migrate entity {$entity->id}: {$e->getMessage()}");
                    info('*********************** Failed to migrate entity details against ID: '.$entity->id.' **********************');
                }
            }
        });

        $jsonEncodeFailedDetails = json_encode($failedDetails);

        info("Migration completed: {$migratedCount} entities migrated, {$skippedCount} entities skipped (already existed), {$failedCount} entities failed.");
        info("Failed migration details: {$jsonEncodeFailedDetails}");
        info('End creating new insured records for entities at '.now());

        info('------------------- Entities Insured Migration Command Ended At: '.now().' -------------------');

        return Command::SUCCESS;
    }

    private function createCustomerInsuredMappingsForEntity(Insured $insured, Entity $entity)
    {
        info('Creating customer-insured mappings for entity '.$entity->id.' and insured '.$insured->id.' at '.now());
        $quoteRequestEntityMappings = QuoteRequestEntityMapping::where('entity_id', $entity->id)->get();

        if (empty($quoteRequestEntityMappings->toArray())) {
            info('##################### Entity '.$entity->id.' quote request mapping not found ##############################');
        }

        foreach ($quoteRequestEntityMappings as $quoteRequestEntityMapping) {
            $quoteObject = $this->genericQueriesAllLobs->getQuoteObject(
                QuoteTypes::getName($quoteRequestEntityMapping->quote_type_id)?->value,
                $quoteRequestEntityMapping->quote_request_id
            );

            if (! $quoteObject) {
                throw new \Exception('Quote object not found for quote request entity mapping. Quote Type ID: '.
                    $quoteRequestEntityMapping->quote_type_id.' Quote Request ID: '.$quoteRequestEntityMapping->quote_request_id);
            }

            CustomerInsured::updateOrCreate([
                'customer_id' => $quoteObject->customer_id,
                'insured_id' => $insured->id,
                'quote_type_id' => $quoteRequestEntityMapping->quote_type_id,
                'quote_request_id' => $quoteRequestEntityMapping->quote_request_id,
            ], []);
        }

        $this->migrateEntityKycDetailsToInsuredKyc($insured, $entity);

        info('End creating customer-insured mappings for entity '.$entity->id.' and insured '.$insured->id.' at '.now());
    }

    private function migrateEntityKycDetailsToInsuredKyc(Insured $insured, Entity $entity)
    {
        info('Migrating Entity KYC details to insured KYC for entity '.$entity->id.' and insured '.$insured->id.' at '.now());
        $insuredKyc = InsuredKyc::where('insured_id', $insured->id)->first();
        if ($insuredKyc) {
            info('Insured KYC already exist for entity '.$entity->id.' and insured '.$insured->id.' at '.now());

            return;
        }

        InsuredKyc::create([
            // Contact info columns
            'insured_id' => $insured->id,
            'first_name' => $entity->kyc_first_name ?? null,
            'last_name' => $entity->kyc_last_name ?? null,
            'mobile_no' => $entity->mobile_no ?? null,
            'email' => $entity->email ?? null,
            'website' => $entity->website ?? null,
            'legal_structure' => $entity->legal_structure ?? null,

            // Country and address information
            'country_of_corporation' => $entity->country_of_corporation ?? null,
            'registered_address' => $entity->registered_address ?? null,
            'communication_address' => $entity->communication_address ?? null,

            // ID related columns
            'id_type' => $entity->id_type ?? null,
            'id_number' => $entity->id_number ?? null,
            'id_issuance_date' => $entity->id_issuance_date ?? null,
            'id_expiry_date' => $entity->id_expiry_date ?? null,
            'issuance_place' => $entity->issuance_place ?? null,
            'id_issuance_authority' => $entity->id_issuance_authority ?? null,

            // Compliance columns
            'pep' => $entity->pep ?? null,
            'financial_sanctions' => $entity->financial_sanctions ?? null,
            'dual_nationality' => $entity->dual_nationality ?? null,

            // FATF and sanctions columns
            'customer_tenure' => $entity->customer_tenure ?? null,
            'transaction_volume' => $entity->transaction_volume ?? null,
            'transaction_activities' => $entity->transaction_activities ?? null,
            'transaction_pattern' => $entity->transaction_pattern ?? null,
            'mode_of_contact' => $entity->mode_of_contact ?? null,
            'mode_of_delivery' => $entity->mode_of_delivery ?? null,
            'in_sanction_list' => $entity->in_sanction_list ?? null,
            'is_sanction_match' => $entity->is_sanction_match ?? null,
            'in_fatf' => $entity->in_fatf ?? null,
            'is_owner_high_risk' => $entity->is_owner_high_risk ?? null,
            'deal_sanction_list' => $entity->deal_sanction_list ?? null,
            'is_operation_high_risk' => $entity->is_operation_high_risk ?? null,
            'is_adverse_media' => $entity->in_adverse_media ?? null,
            'is_owner_pep' => $entity->is_owner_pep ?? null,
            'is_controlling_pep' => $entity->is_controlling_pep ?? null,
            'business_activity_id' => $entity->business_activity_id ?? null,
            'entity_id' => $entity->id,
        ]);

        info('End migrating Entity KYC details to insured KYC for entity '.$entity->id.' and insured '.$insured->id.' at '.now());
    }
}
