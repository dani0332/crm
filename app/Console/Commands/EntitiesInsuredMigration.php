<?php

namespace App\Console\Commands;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\QuoteRequestEntityMapping;
use Illuminate\Console\Command;
use App\Enums\QuoteTypes;
use App\Models\InsuredKycDetail;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\DB;

/**
 * Entities Insured Migration Command
 *
 * This command migrates data from the legacy Entity structure to the new Insured table structure.
 * It handles the migration of entities, their KYC details, and creates proper mappings to customers.
 * This command is deprecated and should be removed after the migration is completed.
 *
 * @package App\Console\Commands
 */
class EntitiesInsuredMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:entities-insured';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Entity data to the Insured table structure, this command is deprecated and should be removed after the migration is completed';
    
    /**
     * Generic queries for all lines of business
     */
    protected $genericQueriesAllLobs;
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->genericQueriesAllLobs = new class { use GenericQueriesAllLobs; };
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('------------------- Entities Insured Migration Command Started At: '.now().' -------------------');

        info('EntitiesMigrationCommand: Starting migration of Entity data to the Insured structure at ' . now());

        info('Updating existing insured records to customer type Individual at ' . now());
        Insured::where(function($query) {
            $query->whereNull('customer_type')
                  ->orWhere('customer_type', '');
        })->where('entity_id', null)->update(['customer_type' => CustomerTypeEnum::Individual]);
        info('End updating existing insured records to customer type Individual at ' . now());

        info('Creating new insured records for entities at ' . now());

        // Count metrics for reporting
        $migratedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        Entity::chunk(100, function ($entities) use (&$migratedCount, &$skippedCount, &$failedCount) {
            foreach ($entities as $entity) {
                info('Creating new insured record for entity ' . $entity->id);
                $isEntityAlreadyCreated = Insured::where('entity_id', $entity->id)->first();
                if ($isEntityAlreadyCreated) {
                    info('Entity ' . $entity->id . ' already exists in the insured table');
                    $skippedCount++;
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    $insured = Insured::create([
                        'customer_type' => CustomerTypeEnum::Entity,
                        'code' => $entity->code,
                        'trade_license_no' => $entity->trade_license_no,
                        'company_name' => $entity->company_name,
                        'company_address' => $entity->address ?? null,
                        'industry_type_code' => $entity->industry_type_code ?? null,
                        'emirate_of_registration_id' => $entity->emirate_of_registration_id ?? null,
                        'sage_customer_number' => $entity->sage_customer_number ?? null,
                        'entity_id' => $entity->id,
                    ]);
    
                    $this->createCustomerInsuredMappingsForEntity($insured, $entity);
                    
                    DB::commit();
                    $migratedCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedCount++;
                    info("Failed to migrate entity {$entity->id}: {$e->getMessage()}", ['exception' => $e]);
                }
            }
        });
        
        info("Migration completed: {$migratedCount} entities migrated, {$skippedCount} entities skipped (already existed), {$failedCount} entities failed.");
        info('End creating new insured records for entities at ' . now());

        info('EntitiesMigrationCommand: End migration of Entity data to the new Insured structure at ' . now());
        return Command::SUCCESS;
    }

    /**
     * Create customer-insured mappings for a given entity
     *
     * @param Insured $insured
     * @param Entity $entity
     * @return void
     */
    private function createCustomerInsuredMappingsForEntity(Insured $insured, Entity $entity)
    {
        info('Creating customer-insured mappings for entity ' . $entity->id . ' and insured ' . $insured->id . ' at ' . now());
        $quoteRequestEntityMappings = QuoteRequestEntityMapping::where('entity_id', $entity->id)->get();
        foreach ($quoteRequestEntityMappings as $quoteRequestEntityMapping) {
            $quoteObject = $this->genericQueriesAllLobs->getQuoteObject(
                QuoteTypes::getName($quoteRequestEntityMapping->quote_type_id)?->value, 
                $quoteRequestEntityMapping->quote_request_id
            );
            
            if (!$quoteObject) {
                info('Quote object not found for quote request entity mapping. Quote Type: ' . 
                     QuoteTypes::getName($quoteRequestEntityMapping->quote_type_id)?->value . 
                     ' Quote Request ID: ' . $quoteRequestEntityMapping->quote_request_id);
                continue;
            }

            CustomerInsured::updateOrCreate([
                'customer_id' => $quoteObject->customer_id,
                'insured_id' => $insured->id,
                'quote_type_id' => $quoteRequestEntityMapping->quote_type_id,
                'quote_request_id' => $quoteRequestEntityMapping->quote_request_id,
            ], []);

            $this->migrateEntityKycDetailsToInsuredKycDetails($insured, $entity, $quoteObject->customer_id);
        }
        info('End creating customer-insured mappings for entity ' . $entity->id . ' and insured ' . $insured->id . ' at ' . now());
    }

    /**
     * Migrate entity KYC details to insured KYC details
     *
     * @param Insured $insured
     * @param Entity $entity
     * @param int $customerId
     * @return void
     */
    private function migrateEntityKycDetailsToInsuredKycDetails(Insured $insured, Entity $entity, int $customerId)
    {
        info('Migrating Entity KYC details to insured KYC details for entity ' . $entity->id . ' and insured ' . $insured->id . ' at ' . now());
        $insuredKycDetails = InsuredKycDetail::where('insured_id', $insured->id)->first();
        if ($insuredKycDetails) {
            info('Insured KYC details already exist for entity ' . $entity->id . ' and insured ' . $insured->id . ' at ' . now());
            return;
        }

        InsuredKycDetail::create([
            // Contact info columns
            'insured_id' => $insured->id,
            'customer_id' => $customerId,
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

        info('End migrating Entity KYC details to insured KYC details for entity ' . $entity->id . ' and insured ' . $insured->id . ' at ' . now());
    }
} 