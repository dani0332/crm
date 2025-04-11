<?php

namespace App\Console\Commands;

use App\Enums\CustomerTypeEnum;
use App\Models\Customer;
use App\Models\CustomerDetail;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\InsuredKycDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Individual KYC Details Migration Command
 *
 * This command migrates data from the CustomerDetail tables to the InsuredKycDetail table structure.
 * It handles the migration of individual customer KYC details to the new InsuredKycDetail structure.
 * This command is deprecated and should be removed after the migration is completed.
 *
 * @package App\Console\Commands
 */
class IndividualKycDetailsMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:individual-kyc-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate Individual customer KYC details to the InsuredKycDetail table structure, this command is deprecated and should be removed after the migration is completed';
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('------------------- Individual KYC Details Migration Command Started At: '.now().' -------------------');

        info('IndividualKycDetailsMigration: Starting migration of Individual customer KYC details to the InsuredKycDetail structure at ' . now());

        // Count metrics for reporting
        $migratedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // Get all customers with customer details
        Customer::with('detail')->chunk(100, function ($customers) use (&$migratedCount, &$skippedCount, &$failedCount) {
            foreach ($customers as $customer) {
                info('Processing customer ' . $customer->id);
                
                // Check if customer already has insured entry
                $insured = $this->getOrCreateInsuredForCustomer($customer);
                
                if (!$insured) {
                    info('Could not create or find insured record for customer ' . $customer->id);
                    $failedCount++;
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // Check if KYC details already exist for this insured
                    $insuredKycDetails = InsuredKycDetail::where('insured_id', $insured->id)->first();
                    if ($insuredKycDetails) {
                        info('Insured KYC details already exist for customer ' . $customer->id . ' and insured ' . $insured->id);
                        $skippedCount++;
                        DB::commit();
                        continue;
                    }
                    
                    // Migrate customer KYC details to InsuredKycDetail
                    $this->migrateCustomerKycDetailsToInsuredKycDetails($insured, $customer);
                    
                    DB::commit();
                    $migratedCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedCount++;
                    info("Failed to migrate KYC details for customer {$customer->id}: {$e->getMessage()}", ['exception' => $e]);
                }
            }
        });
        
        info("Migration completed: {$migratedCount} customer KYC details migrated, {$skippedCount} skipped (already existed), {$failedCount} failed.");
        info('End migrating Individual customer KYC details at ' . now());

        info('IndividualKycDetailsMigration: End migration of Individual customer KYC details to the InsuredKycDetail structure at ' . now());
        return Command::SUCCESS;
    }

    /**
     * Get or create Insured record for the Customer
     *
     * @param Customer $customer
     * @return Insured|null
     */
    private function getOrCreateInsuredForCustomer(Customer $customer)
    {
        // Try to get existing insured for this customer through CustomerInsured mapping
        $customerInsured = CustomerInsured::where('customer_id', $customer->id)->first();
        if ($customerInsured) {
            return Insured::find($customerInsured->insured_id);
        }
        
        // Create new insured record for this customer
        $insured = Insured::create([
            'customer_type' => CustomerTypeEnum::Individual,
            'first_name' => $customer->insured_first_name ?? $customer->first_name,
            'last_name' => $customer->insured_last_name ?? $customer->last_name,
            'dob' => $customer->dob,
            'gender' => $customer->gender,
            'mobile_no' => $customer->mobile_no,
            'email' => $customer->email,
            'nationality_id' => $customer->nationality_id,
            'code' => 'IND-' . $customer->id
        ]);
        
        // Create the CustomerInsured mapping
        CustomerInsured::create([
            'customer_id' => $customer->id,
            'insured_id' => $insured->id
        ]);
        
        return $insured;
    }

    /**
     * Migrate customer KYC details to insured KYC details
     *
     * @param Insured $insured
     * @param Customer $customer
     * @return void
     */
    private function migrateCustomerKycDetailsToInsuredKycDetails(Insured $insured, Customer $customer)
    {
        info('Migrating KYC details for customer ' . $customer->id . ' to insured ' . $insured->id);
        
        $customerDetail = $customer->detail;
        if (!$customerDetail) {
            info('No customer details found for customer ' . $customer->id);
            return;
        }
        
        InsuredKycDetail::create([
            // Customer info
            'insured_id' => $insured->id,
            'customer_id' => $customer->id,
            'first_name' => $customer->insured_first_name ?? $customer->first_name,
            'last_name' => $customer->insured_last_name ?? $customer->last_name,
            'mobile_no' => $customer->mobile_no,
            'email' => $customer->email,
            
            // Country and address information
            'country_of_residence' => $customerDetail->country_of_residence,
            'place_of_birth' => $customerDetail->place_of_birth,
            'residential_status' => $customerDetail->residential_status,
            'residential_address' => $customerDetail->residential_address,
            
            // ID related columns
            'id_type' => $customerDetail->id_type,
            'id_number' => $customerDetail->id_number,
            'id_issuance_date' => $customerDetail->id_issuance_date,
            'id_expiry_date' => $customerDetail->id_expiry_date,
            'id_issuance_authority' => $customerDetail->id_issuance_authority,
            
            // Employment information
            'source_of_income' => $customerDetail->source_of_income,
            'employer_company_name' => $customerDetail->employer_company_name,
            'job_title' => $customerDetail->job_title,
            'employment_sector' => $customerDetail->employment_sector,
            'position_in_company' => $customerDetail->position_in_company,
            
            // Business information
            'trade_license_no' => $customerDetail->trade_license_no,
            
            // Compliance columns
            'pep' => $customerDetail->pep,
            'financial_sanctions' => $customerDetail->financial_sanctions,
            'dual_nationality' => $customerDetail->dual_nationality,
            
            // Transaction patterns
            'customer_tenure' => $customerDetail->customer_tenure,
            'transaction_pattern' => $customerDetail->transaction_pattern,
            'premium_tenure' => $customerDetail->premium_tenure,
            'mode_of_contact' => $customerDetail->mode_of_contact,
            'mode_of_delivery' => $customerDetail->mode_of_delivery,
            
            // Risk factors
            'in_sanction_list' => $customerDetail->in_sanction_list,
            'deal_sanction_list' => $customerDetail->deal_sanction_list,
            'is_operation_high_risk' => $customerDetail->is_operation_high_risk,
            'is_partner' => $customerDetail->is_partner,
        ]);
        
        info('End migrating KYC details for customer ' . $customer->id . ' to insured ' . $insured->id);
    }
} 