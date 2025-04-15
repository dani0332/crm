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

        // Step 1: Get Customer details with customer 
        // Step 2: Search customer details id type and id number in insured table
        // Step 3: If found, insured kyc details mai insured id k 7 customer details ki entry with customer details id (quote type and quote request id would be null)
        // Step 4: If not found, create a new insured record and insured kyc details mai customer details ki entry with customer details id (quote type and quote request id would be null)

        // Count metrics for reporting
        $migratedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // Step 1: Get Customer details with customer
        CustomerDetail::with('customer')->chunk(100, function ($customerDetails) use (&$migratedCount, &$skippedCount, &$failedCount) {
            foreach ($customerDetails as $customerDetail) {
                info('Processing customer detail ' . $customerDetail->id);
                
                $customer = $customerDetail->customer;
                if (!$customer) {
                    info('No customer found for customer detail ' . $customerDetail->id);
                    $skippedCount++;
                    continue;
                }
                
                try {
                    DB::beginTransaction();

                    $formattedIdNumber = $customerDetail->id_number;
                    if ($customerDetail->id_type === 'emiratesId' && strlen($customerDetail->id_number) === 15) {
                        $formattedIdNumber = substr($customerDetail->id_number, 0, 3) . '-' . 
                            substr($customerDetail->id_number, 3, 4) . '-' . 
                            substr($customerDetail->id_number, 7, 7) . '-' . 
                            substr($customerDetail->id_number, 14, 1);
                        
                        info('Emirates ID detected, formatted to: ' . $formattedIdNumber);
                    }
                    
                    $insured = Insured::where('id_type', $customerDetail->id_type)
                        ->when($customerDetail->id_type == 'passport', function ($query) use ($customerDetail) {
                            $query->where('id_number', str_replace('-', '', $customerDetail->id_number));
                        })
                        ->when($customerDetail->id_type == 'emiratesId', function ($query) use ($formattedIdNumber) {
                            $query->where('id_number', $formattedIdNumber);
                        })
                        ->first();
                    

                    info('Searching for ID type: ' . $customerDetail->id_type . ', original ID number: ' . $customerDetail->id_number . ', formatted: ' . $formattedIdNumber);
                    
                    if (!$insured) {
                        info('No matching insured found, creating new insured record for customer ' . $customer->id);
                        $insured = Insured::create([
                            'customer_type' => CustomerTypeEnum::Individual,
                            'first_name' => $customer->insured_first_name ?? $customer->first_name,
                            'last_name' => $customer->insured_last_name ?? $customer->last_name,
                            'dob' => $customer->dob,
                            'gender' => $customer->gender,
                            'mobile_no' => $customer->mobile_no,
                            'email' => $customer->email,
                            'nationality_id' => $customer->nationality_id,
                            'id_type' => $customerDetail->id_type,
                            'id_number' => $formattedIdNumber,
                            'code' => 'IND-' . $customer->id
                        ]);
                        
                        CustomerInsured::create([
                            'customer_id' => $customer->id,
                            'insured_id' => $insured->id
                        ]);
                    } else {
                        info('Found matching insured record ID: ' . $insured->id . ' for customer ' . $customer->id);
                        $customerInsured = CustomerInsured::where('customer_id', $customer->id)->where('insured_id', $insured->id)->first();
                        if (!$customerInsured) {
                            CustomerInsured::create([
                                'customer_id' => $customer->id,
                                'insured_id' => $insured->id
                            ]);
                        }
                    }
                    
                    $insuredKycDetail = InsuredKycDetail::where('insured_id', $insured->id)->where('customer_detail_id', $customerDetail->id)->first();
                    if ($insuredKycDetail) {
                        info('Insured KYC details already exist for customer detail ' . $customerDetail->id);
                        $skippedCount++;
                        DB::commit();
                        continue;
                    }
                    
                    InsuredKycDetail::create([
                        'insured_id' => $insured->id,
                        'customer_id' => $customer->id,
                        'customer_details_id' => $customerDetail->id,
                        
                        'country_of_residence' => $customerDetail->country_of_residence,
                        'place_of_birth' => $customerDetail->place_of_birth,
                        'residential_status' => $customerDetail->residential_status,
                        'residential_address' => $customerDetail->residential_address,
                        
                        'id_type' => $customerDetail->id_type,
                        'id_number' => $customerDetail->id_number,
                        'id_issuance_date' => $customerDetail->id_issuance_date,
                        'id_expiry_date' => $customerDetail->id_expiry_date,
                        
                        'source_of_income' => $customerDetail->source_of_income,
                        'employer_company_name' => $customerDetail->employer_company_name,
                        'job_title' => $customerDetail->job_title,
                        'employment_sector' => $customerDetail->employment_sector,
                        'position_in_company' => $customerDetail->position_in_company,
                        
                        'trade_license_no' => $customerDetail->trade_license_no,
                        
                        'pep' => $customerDetail->pep,
                        'financial_sanctions' => $customerDetail->financial_sanctions,
                        'dual_nationality' => $customerDetail->dual_nationality,
                        
                        'customer_tenure' => $customerDetail->customer_tenure,
                        'transaction_pattern' => $customerDetail->transaction_pattern,
                        'premium_tenure' => $customerDetail->premium_tenure,
                        'mode_of_contact' => $customerDetail->mode_of_contact,
                        'mode_of_delivery' => $customerDetail->mode_of_delivery,
                        
                        'risk_score' => $customerDetail->risk_score,
                        'in_sanction_list' => $customerDetail->in_sanction_list,
                        'deal_sanction_list' => $customerDetail->deal_sanction_list,
                        'is_operation_high_risk' => $customerDetail->is_operation_high_risk,
                        'is_partner' => $customerDetail->is_partner,
                    ]);
                    
                    info('Migrated KYC details for customer detail ' . $customerDetail->id . ' to insured ' . $insured->id);
                    $migratedCount++;
                    
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedCount++;
                    info("Failed to migrate KYC details for customer detail {$customerDetail->id}: {$e->getMessage()}", ['exception' => $e]);
                }
            }
        });
        
        info("Migration completed: {$migratedCount} customer KYC details migrated, {$skippedCount} skipped (already existed), {$failedCount} failed.");
        info('End migrating Individual customer KYC details at ' . now());

        info('IndividualKycDetailsMigration: End migration of Individual customer KYC details to the InsuredKycDetail structure at ' . now());
        return Command::SUCCESS;
    }
} 