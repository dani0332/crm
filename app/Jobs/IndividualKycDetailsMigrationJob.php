<?php

namespace App\Jobs;

use App\Enums\CustomerTypeEnum;
use App\Models\CustomerDetail;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\InsuredKyc;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Individual KYC Details Migration Job
 * This job migrates data from the CustomerDetail tables to the InsuredKycDetail table structure.
 * It handles the migration of individual customer KYC details to the new InsuredKycDetail structure.
 * This job is designed to be resumable - it will only process customer details that haven't been migrated yet.
 */
class IndividualKycDetailsMigrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('------------------- Individual customer KYC Details from Customer Details to Insured KYC Migration Job Started At: '.now().' -------------------');
        $totalCustomerDetails = CustomerDetail::count();
        $alreadyMigratedCount = Insured::whereNotNull('customer_details_id')->count();

        Log::info("Total customer details: {$totalCustomerDetails}, Already migrated: {$alreadyMigratedCount}, Remaining: ".($totalCustomerDetails - $alreadyMigratedCount));

        // Skip if everything is already migrated
        if ($alreadyMigratedCount >= $totalCustomerDetails) {
            Log::info('All customer details have already been migrated. Nothing to do.');

            return;
        }

        $migratedCount = $skippedCount = $failedCount = 0;
        $failedDetails = [];

        // Query only customer details that haven't been migrated yet using subqueries
        $customerDetailsQuery = CustomerDetail::with('customer')->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('insureds')
                ->whereColumn('insureds.customer_details_id', 'customer_details.id')
                ->whereNotNull('insureds.customer_details_id');
        })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('insured_kycs')
                    ->whereColumn('insured_kycs.customer_details_id', 'customer_details.id')
                    ->whereNotNull('insured_kycs.customer_details_id');
            });

        info('Remaining customer details to process: '.$customerDetailsQuery->count());

        $customerDetailsQuery->chunk(50, function ($customerDetails) use (&$migratedCount, &$skippedCount, &$failedCount, &$failedDetails) {
            // Start a transaction for the entire chunk
            DB::beginTransaction();
            try {
                foreach ($customerDetails as $customerDetail) {
                    $customer = $customerDetail->customer;
                    if (! $customer) {
                        info('Command:IndividualKycDetailsMigration - No customer found against customer detail ID:'.$customerDetail->id);
                        $skippedCount++;

                        continue;
                    }

                    Log::info('*********************** Migrating Individual customer KYC details against customer detail ID:'.$customerDetail->id.' **********************');

                    $normalizedIdNumber = $customerDetail->id_number;
                    if ($customerDetail->id_type === 'emiratesId' && strlen(str_replace('-', '', $normalizedIdNumber)) === 15) {
                        $normalizedIdNumber = str_replace('-', '', $normalizedIdNumber);
                        $normalizedIdNumber = substr($normalizedIdNumber, 0, 3).'-'.
                            substr($normalizedIdNumber, 3, 4).'-'.
                            substr($normalizedIdNumber, 7, 7).'-'.
                            substr($normalizedIdNumber, 14, 1);
                    }

                    $insured = Insured::where(['id_type' => $customerDetail->id_type, 'id_number' => $normalizedIdNumber])->first();

                    if (! $insured) {
                        Log::info('Creating new insured record against Customer ID: '.$customer->id.' and Customer Detail ID: '.$customerDetail->id);
                        $insured = Insured::create([
                            'customer_type' => CustomerTypeEnum::Individual,
                            'first_name' => $customer->insured_first_name ?? $customer->first_name,
                            'last_name' => $customer->insured_last_name ?? $customer->last_name,
                            'dob' => $customer->dob,
                            'nationality_id' => $customer->nationality_id,
                            'gender' => $customer->gender,
                            'id_type' => $customerDetail->id_type,
                            'id_number' => $normalizedIdNumber,
                            'code' => 'IND-'.$customer->id,
                            'customer_details_id' => $customerDetail->id,
                        ]);

                        Log::info('Creating customer-insured mappings for customer details '.$customerDetail->id.' and customer '.$customer->id.' and insured '.$insured->id.' at '.now());
                        CustomerInsured::create([
                            'customer_id' => $customer->id,
                            'insured_id' => $insured->id,
                        ]);
                    } else {
                        Log::info('Insured found against Customer Detail ID: '.$customerDetail->id.' and Insured ID: '.$insured->id);
                        $customerInsured = CustomerInsured::where('customer_id', $customer->id)->where('insured_id', $insured->id)->first();
                        if (! $customerInsured) {
                            Log::info('Creating customer-insured mappings for customer details '.$customerDetail->id.' and customer '.$customer->id.' and insured '.$insured->id.' at '.now());
                            CustomerInsured::create([
                                'customer_id' => $customer->id,
                                'insured_id' => $insured->id,
                            ]);
                        }
                    }

                    // Double-check if KYC details already exist
                    $insuredKyc = InsuredKyc::where('insured_id', $insured->id)->first();

                    if ($insuredKyc) {
                        // If the existing KYC is for the same customer_details_id, skip
                        if ($insuredKyc->customer_details_id == $customerDetail->id) {
                            Log::info('Insured KYC details already exist against customer detail ID:'.$customerDetail->id.' and insured ID:'.$insured->id);
                            $skippedCount++;

                            continue;
                        }

                        // If the KYC exists but with a different customer_details_id, check the customer_id and add mapping
                        Log::info('Insured KYC exists for insured ID:'.$insured->id.' but with different customer detail ID. Current:'.$customerDetail->id.', Existing:'.$insuredKyc->customer_details_id);

                        // Get customer_id from the customer_details
                        $customerFromDetail = $customerDetail->customer;
                        if ($customerFromDetail) {
                            // Use updateOrCreate to either create a new mapping or skip if it already exists
                            Log::info('Creating customer-insured mapping for customer '.$customerFromDetail->id.' and insured '.$insured->id);
                            CustomerInsured::updateOrCreate(
                                [
                                    'customer_id' => $customerFromDetail->id,
                                    'insured_id' => $insured->id,
                                ],
                                []
                            );
                        }

                        $skippedCount++;

                        continue;
                    }

                    Log::info('Creating Insured KYC against customer details '.$customerDetail->id.' and insured '.$insured->id.' at '.now());
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

                    $migratedCount++;
                    Log::info('*********************** Migrated Individual customer KYC details against Customer Detail ID: '.$customerDetail->id.' and Insured ID: '.$insured->id.' **********************');
                }
                // Commit transaction for the entire chunk
                DB::commit();
                info("Successfully processed chunk of {$customerDetails->count()} records");

            } catch (\Exception $e) {
                // Rollback the entire chunk
                DB::rollBack();
                $failedCount++;

                // Get IDs of all customer details in this chunk
                $customerDetailIds = $customerDetails->pluck('id')->implode(',');

                $failedDetails['entity_id'][] = $customerDetailIds;
                $failedDetails['message'][] = "Failed to process chunk: {$e->getMessage()}";

                info("Failed to process chunk with customer detail IDs: {$customerDetailIds}. Error: {$e->getMessage()}");
            }
        });

        $jsonEncodeFailedDetails = json_encode($failedDetails);

        Log::info("Migration completed: individual customer kyc details - Migrated:{$migratedCount}, Skipped: {$skippedCount} (already existed), Failed:{$failedCount}.");

        if ($failedCount > 0) {
            Log::info("Failed migration details: {$jsonEncodeFailedDetails}");
        }

        Log::info('End migrating individual customer KYC details at '.now());
        Log::info('------------------- Individual KYC Details from Customer Details to Insured KYC Migration Job Ended At: '.now().' -------------------');
    }
}
