<?php

namespace App\Console\Commands;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerDetail;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\InsuredKyc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Individual KYC Details Migration Command
 * This command migrates data from the CustomerDetail tables to the InsuredKycDetail table structure.
 * It handles the migration of individual customer KYC details to the new InsuredKycDetail structure.
 * This command is deprecated and should be removed after the migration is completed.
 */

class IndividualKycDetailsMigration extends Command
{

    protected $signature = 'migrate:individual-kyc-details';
    protected $description = 'Migrate Individual customer KYC details to the InsuredKycDetail table structure, this command is deprecated and should be removed after the migration is completed';
    
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        info('------------------- Individual customer KYC Details from Customer Details to Insured KYC Migration Command Started At: '.now().' -------------------');

        $migratedCount = $skippedCount = $failedCount = 0;
        $failedDetails = [];

        CustomerDetail::with('customer')->where('id', '>', 2200)->chunk(500, function ($customerDetails) use (&$migratedCount, &$skippedCount, &$failedCount) {
            foreach ($customerDetails as $customerDetail) {

                $isIndividualCustomerDetailsAlreadyCreated = Insured::where('customer_details_id', $customerDetail->id)->first();
                if ($isIndividualCustomerDetailsAlreadyCreated) {
                    info('Command:IndividualKycDetailsMigration - Data against Customer Detail ID: ' . $customerDetail->id . ' already exists in the insured table');
                    $skippedCount++;
                    continue;
                }

                $customer = $customerDetail->customer;
                if (!$customer) {
                    info('Command:IndividualKycDetailsMigration - No customer found against customer detail ID:' . $customerDetail->id);
                    $skippedCount++;
                    continue;
                }

                info('*********************** Migrating Individual customer KYC details against customer detail ID:' . $customerDetail->id .' **********************');
                
                try {
                    $normalizedIdNumber = $customerDetail->id_number;
                    if ($customerDetail->id_type === 'emiratesId' && strlen(str_replace('-', '', $normalizedIdNumber)) === 15) {
                        $normalizedIdNumber = str_replace('-', '', $normalizedIdNumber);
                        $normalizedIdNumber = substr($normalizedIdNumber, 0, 3) . '-' . 
                            substr($normalizedIdNumber, 3, 4) . '-' . 
                            substr($normalizedIdNumber, 7, 7) . '-' . 
                            substr($normalizedIdNumber, 14, 1);
                    }
                    
                    $insured = Insured::where(['id_type' => $customerDetail->id_type, 'id_number' => $normalizedIdNumber])->first();

                    DB::beginTransaction();
                    if (!$insured) {    
                        info('Creating new insured record against Customer ID: ' . $customer->id. ' and Customer Detail ID: ' . $customerDetail->id);
                        $insured = Insured::create([
                            'customer_type' => CustomerTypeEnum::Individual,
                            'first_name' => $customer->insured_first_name ?? $customer->first_name,
                            'last_name' => $customer->insured_last_name ?? $customer->last_name,
                            'dob' => $customer->dob,
                            'nationality_id' => $customer->nationality_id,
                            'gender' => $customer->gender,
                            'id_type' => $customerDetail->id_type,
                            'id_number' => $normalizedIdNumber,
                            'code' => 'IND-' . $customer->id,
                            'customer_details_id' => $customerDetail->id,
                        ]);

                        info('Creating customer-insured mappings for customer details ' . $customerDetail->id . ' and customer ' . $customer->id . ' and insured ' . $insured->id . ' at ' . now());
                        CustomerInsured::create([
                            'customer_id' => $customer->id,
                            'insured_id' => $insured->id
                        ]);
                    } else {
                        info('Insured found against Customer Detail ID: ' . $customerDetail->id . ' and Insured ID: ' . $insured->id);
                        $customerInsured = CustomerInsured::where('customer_id', $customer->id)->where('insured_id', $insured->id)->first();
                        if (!$customerInsured) {
                            info('Creating customer-insured mappings for customer details ' . $customerDetail->id . ' and customer ' . $customer->id . ' and insured ' . $insured->id . ' at ' . now());
                            CustomerInsured::create([
                                'customer_id' => $customer->id,
                                'insured_id' => $insured->id
                            ]);
                        }
                    }
                    
                    $insuredKyc = InsuredKyc::where('insured_id', $insured->id)->where('customer_details_id', $customerDetail->id)->first();
                    if ($insuredKyc) {
                        info('Insured KYC details already exist against customer detail ID:' . $customerDetail->id . ' and insured ID:' . $insured->id);
                        $skippedCount++;
                        DB::commit();
                        continue;
                    }

                    info('Creating Insured KYC against customer details ' . $customerDetail->id . ' and insured ' . $insured->id . ' at ' . now());
                    InsuredKyc::create([
                        'insured_id' => $insured->id,
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
                    
                    DB::commit();
                    $migratedCount++;
                    info('*********************** Migrated Individual customer KYC details against Customer Detail ID: ' . $customerDetail->id . ' and Insured ID: ' . $insured->id . ' **********************');

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedDetails['entity_id'][] = $customerDetail->id;
                    $failedDetails['message'][$customerDetail->id] = 'Failed to migrate KYC details for customer detail. message: '.$e->getMessage();
                    $failedCount++;
                    info("Failed to migrate individual customer KYC details against customer detail ID: {$customerDetail->id}: - message: {$e->getMessage()}");
                    info('*********************** Failed to migrate individual customer KYC details against customer detail ID: ' . $customerDetail->id .' **********************');
                }
            }
        });

        $jsonEncodeFailedDetails = json_encode($failedDetails);

        info("Migration completed: individual customer kyc details - Migrated:{$migratedCount}, Skipped: {$skippedCount} (already existed), Failed:{$failedCount}.");
        info("Failed migration details: {$jsonEncodeFailedDetails}");
        info('End migrating individual customer KYC details at ' . now());

        info('------------------- Individual KYC Details from Customer Details to Insured KYC Migration Command Ended At: '.now().' -------------------');
        return Command::SUCCESS;
    }
} 