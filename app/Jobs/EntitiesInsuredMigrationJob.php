<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CustomerInsured;
use App\Models\Entity;
use App\Models\HomeQuote;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\QuoteRequestEntityMapping;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Entities Insured Migration Job
 * This job migrates data from the legacy Entity structure to the new Insured table structure.
 * It handles the migration of entities, their KYC details, and creates proper mappings to customers.
 * This job is designed to be resumable - it will only process entities that haven't been migrated yet.
 */
class EntitiesInsuredMigrationJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    const CLASS_NAME = 'EntitiesInsuredMigrationJob';

    private $lockPostfix;

    public function __construct()
    {
        $this->lockPostfix = Carbon::now()->format('YmdHi');
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        LoggerService::info(self::CLASS_NAME.' - ------------------- Entities Insured Migration Job Started At: '.now().' -------------------');

        $this->updateExistingRecords();

        $totalEntities = Entity::count();
        $alreadyMigratedCount = Insured::whereNotNull('entity_id')->count();

        LoggerService::info(self::CLASS_NAME." - Total entities: {$totalEntities}, Already migrated: {$alreadyMigratedCount}, Remaining: ".($totalEntities - $alreadyMigratedCount));

        // Skip if everything is already migrated
        if ($alreadyMigratedCount >= $totalEntities) {
            LoggerService::info(self::CLASS_NAME.' - All entities have already been migrated. Nothing to do.');

            return;
        }

        // Process stats
        $failedDetails = [];
        $migratedCount = $skippedCount = $failedCount = 0;

        // Using a subquery instead of plucking all IDs
        $entities = Entity::whereNotIn('id', function ($query) {
            $query->select('insured.entity_id')
                ->from('insured')
                ->whereNotNull('insured.entity_id');
        });

        $entities->chunkById(50, function ($entitiesToProcess) use (&$migratedCount, &$failedCount, &$failedDetails) {
            DB::beginTransaction();
            try {
                foreach ($entitiesToProcess as $entity) {
                    LoggerService::info(self::CLASS_NAME.' - *********************** Migrating entity details against entity id: '.$entity->id.' **********************');
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
                    $migratedCount++;
                    LoggerService::info(self::CLASS_NAME.' - *********************** Migrated entity details against entity id: '.$entity->id.' **********************');
                }
                DB::commit();
                LoggerService::info(self::CLASS_NAME." - Successfully processed chunk of {$entitiesToProcess->count()} records");
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;

                $entityIds = json_encode(array_column($entitiesToProcess->toArray(), 'id'));

                $failedDetails['entity_id'][] = $entityIds;
                $failedDetails['message'][] = "Failed to process chunk: {$e->getMessage()}";

                LoggerService::warning(self::CLASS_NAME.' - Failed to process chunk - Error: '.$e->getMessage(), extra: [
                    'trace' => $e->getTraceAsString()]);
            }
        });

        LoggerService::info(self::CLASS_NAME." - Migration completed: {$migratedCount} entities migrated, {$skippedCount} entities skipped (already existed), {$failedCount} entities failed.");

        if ($failedCount > 0) {
            LoggerService::info(self::CLASS_NAME.' - Migration failed for the following entities', extra: [
                'failed_details' => json_encode($failedDetails),
            ]);
        }
        LoggerService::info(self::CLASS_NAME.' - ------------------- Entities Insured Migration Job Ended At: '.now().' -------------------');
    }

    /**
     * Update existing insured records that don't have a customer type
     */
    private function updateExistingRecords()
    {
        LoggerService::info(self::CLASS_NAME.' - Start updating the existing records in the insured table by setting the customer type to Individual at '.now());

        $insured = Insured::where(function ($query) {
            $query->whereNull('customer_type')
                ->orWhere('customer_type', '');
        })->where('entity_id', null);

        $count = $insured->count();
        if ($count > 0) {
            LoggerService::info(self::CLASS_NAME." - Updating {$count} existing insured records with customer_type = Individual");
            $insured->update(['customer_type' => CustomerTypeEnum::Individual]);
        }

        LoggerService::info(self::CLASS_NAME.' - Existing insured table records updated as Individual successfully at '.now());
    }

    private function createCustomerInsuredMappingsForEntity(Insured $insured, Entity $entity)
    {
        LoggerService::info(self::CLASS_NAME.' - Creating customer-insured mappings for entity id: '.$entity->id.' and insured id: '.$insured->id.' at '.now());
        $quoteRequestEntityMappings = QuoteRequestEntityMapping::where('entity_id', $entity->id)->get();

        if (empty($quoteRequestEntityMappings->toArray())) {
            LoggerService::info(self::CLASS_NAME.' - ##################### entity id: '.$entity->id.' quote request mapping not found ##############################');
        } else {
            // Only process mappings if they exist
            foreach ($quoteRequestEntityMappings as $quoteRequestEntityMapping) {
                if($quoteRequestEntityMapping->quote_type_id == QuoteTypeId::Home) {
                    $quoteObject = HomeQuote::where('id', $quoteRequestEntityMapping->quote_request_id)->first();
                } else {
                    $quoteObject = $this->getQuoteObject(
                        QuoteTypes::getName($quoteRequestEntityMapping->quote_type_id)?->value,
                        $quoteRequestEntityMapping->quote_request_id
                    );
                }

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
        }

        // Always migrate KYC details regardless of mappings
        $this->migrateEntityKycDetailsToInsuredKyc($insured, $entity);
    }

    private function migrateEntityKycDetailsToInsuredKyc(Insured $insured, Entity $entity)
    {
        LoggerService::info(self::CLASS_NAME.' - Migrating Entity KYC details to insured KYC for entity id: '.$entity->id.' and insured id: '.$insured->id.' at '.now());

        // Skip if KYC details are already migrated
        $insuredKyc = InsuredKyc::where('insured_id', $insured->id)->first();
        if ($insuredKyc) {

            return;
        }

        InsuredKyc::create([
            'insured_id' => $insured->id,
            'first_name' => $entity->kyc_first_name ?? null,
            'last_name' => $entity->kyc_last_name ?? null,
            'mobile_no' => $entity->mobile_no ?? null,
            'email' => $entity->email ?? null,
            'website' => $entity->website ?? null,
            'legal_structure' => $entity->legal_structure ?? null,

            'country_of_corporation' => $entity->country_of_corporation ?? null,
            'registered_address' => $entity->registered_address ?? null,
            'communication_address' => $entity->communication_address ?? null,

            'id_type' => $entity->id_type ?? null,
            'id_number' => $entity->id_number ?? null,
            'id_issuance_date' => $entity->id_issuance_date ?? null,
            'id_expiry_date' => $entity->id_expiry_date ?? null,
            'issuance_place' => $entity->issuance_place ?? null,
            'id_issuance_authority' => $entity->id_issuance_authority ?? null,

            'pep' => $entity->pep ?? null,
            'financial_sanctions' => $entity->financial_sanctions ?? null,
            'dual_nationality' => $entity->dual_nationality ?? null,

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
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        LoggerService::error(self::CLASS_NAME.' - Entities Insured Migration Job failed with exception: '.$exception->getMessage(), extra: [
            'trace' => $exception->getTraceAsString()]);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping(self::CLASS_NAME.'-'.$this->lockPostfix))->dontRelease()];
    }
}
