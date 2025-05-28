<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CarPlanAddonsCode;
use App\Enums\CarPlanType;
use App\Enums\carTypeInsuranceCode;
use App\Enums\FetchPlansStatuses;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteSegmentEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RangeLookupIdEnums;
use App\Enums\RangeLookupKeyEnums;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Enums\ThirdPartyTagEnum;
use App\Enums\TiersEnum;
use App\Enums\TravelQuoteEnum;
use App\Exports\RenewalQuotesExport;
use App\Facades\Ken;
use App\Imports\TravelUploadAndCreateImport;
use App\Imports\UploadAndCreateImport;
use App\Imports\UploadAndUpdateHomeImport;
use App\Imports\UploadAndUpdateImport;
use App\Jobs\OCB\SendCarOCBIntroEmailJob;
use App\Jobs\Renewals\CreateRenewalQuotesJob;
use App\Jobs\Renewals\CreateRenewalsWorkflowJob;
use App\Jobs\Renewals\CreateTravelRenewalQuotesJob;
use App\Jobs\Renewals\FetchPlansForHomeRenewalsQuoteJob;
use App\Jobs\Renewals\FetchPlansForRenewalsQuoteJob;
use App\Jobs\Renewals\HomeRenewalBatchEmailJob;
use App\Jobs\Renewals\ProcessRenewalsUploadCreate;
use App\Jobs\Renewals\ProcessRenewalsUploadUpdate;
use App\Jobs\Renewals\ProcessTravelRenewalsUploadCreate;
use App\Jobs\Renewals\RenewalBatchEmailJob;
use App\Jobs\Renewals\UpdateRenewalQuotesJob;
use App\Jobs\ScheduleHomeRenewalOcbEmails;
use App\Jobs\SendPCPCarOCBEmailJob;
use App\Jobs\SendPCPFollowupsJob;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModelDetail;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\CarQuoteValuation;
use App\Models\ClaimHistory;
use App\Models\CurrentlyLocatedIn;
use App\Models\Customer;
use App\Models\Emirate;
use App\Models\HealthPlan;
use App\Models\HomeQuote;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PaymentStatus;
use App\Models\QuoteAdditionalDetail;
use App\Models\QuoteStatus;
use App\Models\QuoteTag;
use App\Models\QuoteType;
use App\Models\RangeLookup;
use App\Models\RenewalBatch;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalStatusProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\SubArea;
use App\Models\Tier;
use App\Models\TravelQuote;
use App\Models\UAELicenseHeldFor;
use App\Models\User;
use App\Models\VehicleType;
use App\Repositories\BusinessQuoteRepository;
use App\Repositories\EmbeddedProductRepository;
use App\Repositories\LookupRepository;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PersonalQuoteSyncTrait;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Batch;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Models\Haystack;
use App\Models\PersonalQuote;

class HomeRenewalService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    // public function updateQuote(RenewalQuoteProcess $renewalQuoteProcess)
    // {

    //     $logPrefix = get_class($this).' FN: updateQuote';
    //     $data = $renewalQuoteProcess->data;

    //     $isNameChanged = false;
    //     $quoteTypeCode = array_key_exists('quote_type', $data) ? $data['quote_type'] : $renewalQuoteProcess->quote_type;
    //     $isQuoteTypeHome = $quoteTypeCode == QuoteTypeShortCode::HOM;

    //     $quote = DB::transaction(function () use ($renewalQuoteProcess, $data, $logPrefix, &$isNameChanged, $quoteTypeCode, $isQuoteTypeHome) {
    //         throw_if(! in_array($quoteTypeCode, [QuoteTypeShortCode::CAR, QuoteTypeShortCode::HOM]), 'Only Insurance Type Car and Home allowed to update lead');

    //         $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();
    //         $isQuoteTypeCar = $quoteTypeCode == QuoteTypeShortCode::CAR;

    //         LoggerService::info($logPrefix.' update quote started for PolicyNo: '.$data['policy_number'].' ID: '.$renewalQuoteProcess->id.' UploadLeadId: '.$renewalUploadLead->id);

    //         $quoteType = $this->getQuoteTypeByShortCode($quoteTypeCode);
    //         // Previous Car Lead
    //         $quoteObject = $this->createQuoteObject(ucfirst($quoteType->code));

    //         $quote = $quoteObject->where('previous_quote_policy_number', $data['policy_number'])
    //             ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
    //             ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))->first();

    //         throw_unless($quote, ('Quote not found for PolicyNumber: '.$data['policy_number'].' EndDate: '.$data['end_date'].' Batch: '.$renewalQuoteProcess->batch));

    //         $newAdvisorId = $this->renewalsAddonService->getUserInfo($data['advisor']);
    //         $advisorId = $quote->advisor_id == null ? $newAdvisorId : $quote->advisor_id;
    //         $carModel = null;

    //         if ($isQuoteTypeCar) {
    //             $carMake = $this->renewalsAddonService->getCarMake($data['make']);
    //             $carModel = $this->renewalsAddonService->getCarModel($data['model'], $carMake);
    //             $previousAdvisor = $this->renewalsAddonService->getUser($data['previous_advisor']);
    //             $claimHistory = $this->getClaimHistory($data['claim_history']);
    //             $nationality = Nationality::where('text', $data['nationality'])->first();
    //             $emirate = Emirate::where('text', $data['registration_location'])->first();
    //             $uaeLicenseHeldFor = UAELicenseHeldFor::where('text', $data['driving_experience'])->first();
    //         } elseif ($isQuoteTypeHome) {
    //             $homeCurrentInsuranceProvider = (! empty($data['current_insurance_provider'])) ? InsuranceProvider::where('code', $data['current_insurance_provider'])->first()->id : null;
    //             $homePossessionTypeId = (! empty($data['you_are_a'])) ? RangeLookup::where('text', $data['you_are_a'])->where('key', RangeLookupKeyEnums::POSSESSION_TYPE)->first()->id : null;
    //             $homeIliveinAccommodationTypeId = (! empty($data['i_live_in_a'])) ? RangeLookup::where('text', $data['i_live_in_a'])->where('key', RangeLookupKeyEnums::ACCOMMODATION_TYPE)->first()->id : null;
    //             $homeOwnerOccupancyTypeId = (! empty($data['occupancy_status_for_owners'])) ? RangeLookup::where('text', $data['occupancy_status_for_owners'])->where('key', RangeLookupKeyEnums::OWNER_OCCUPANCY_TYPE)->first()->id : null;
    //             $homeSubAreaId = (! empty($data['location_area'])) ? SubArea::where('text', $data['location_area'])->first()->id : null;
    //             $homeCoverageTypeId = (! empty($data['cover_required'])) ? RangeLookup::where('text', $data['cover_required'])->where('key', RangeLookupKeyEnums::COVERAGE_TYPE)->first()->id : null;
    //             $homeContents = (! empty($data['contents'])) ? RangeLookup::where('text', $data['contents'])->where('key', RangeLookupKeyEnums::CONTENT_VALUES)->first()->id : null;
    //             $homePersonalBelongings = (! empty($data['personal_belongings'])) ? RangeLookup::where('text', $data['personal_belongings'])->where('key', RangeLookupKeyEnums::PERSONAL_BELONGING_VALUES)->first()->id : null;
    //             $homeBuildingAed = (! empty($data['building'])) ? $data['building'] : null;
    //             $homeInsuranceProvider = (! empty($data['insurance_provider'])) ? InsuranceProvider::where('code', $data['insurance_provider'])->first()->id : null;
    //             $homePlanName = (! empty($data['plan_name'])) ? $data['plan_name'] : null;
    //             $homeClaimsHistory = (! empty($data['claims_history']) && $data['claims_history'] == 'Yes') ? 1 : 0;
    //             $homePremium = (! empty($data['premium'])) ? $data['premium'] : null;
    //             $homeInsurerQuoteNumber = (! empty($data['insurer_quote_no'])) ? $data['insurer_quote_no'] : null;
    //             $homePreviousAdvisorId = (! empty($data['previous_advisor_email'])) ? $this->renewalsAddonService->getUserInfo($data['previous_advisor_email']) : null;
    //         }

    //         LoggerService::info($logPrefix.' fetched options from DB');

    //         if ($carModel) {
    //             $vehicleType = $this->renewalsAddonService->getVehicleType($carModel->vehicle_type_id);
    //         }

    //         if (array_key_exists('product_type', $data) && $data['product_type'] != null) {
    //             $carTypeOfInsurance = $this->renewalsAddonService->getCarTypeOfInsurance($data['product_type']);
    //         }

    //         LoggerService::info($logPrefix.' quote found to update with UUID: '.$quote->uuid);

    //         $customerData = $this->buildCustomerData($data);

    //         // check if name is changed , then run AML again
    //         if ($quote->first_name != $customerData['first_name'] || $quote->last_name != $customerData['last_name']) {
    //             $isNameChanged = true;
    //         }

    //         $isReAssignment = $quote->advisor_id != $advisorId;

    //         $this->updateCustomer($quote, $customerData);

    //         $quoteData = [
    //             'first_name' => $customerData['first_name'],
    //             'last_name' => $customerData['last_name'],
    //             'email' => $customerData['email'],
    //             'mobile_no' => $customerData['mobile_no'],
    //             'previous_policy_expiry_date' => (! empty($data['end_date'])) ? $this->formatDate($data['end_date']) : null,
    //             'previous_policy_start_date' => (! empty($data['start_date'])) ? $this->formatDate($data['start_date']) : null,
    //             'advisor_id' => $advisorId,
    //             'assignment_type' => $advisorId ? ($isReAssignment ? AssignmentTypeEnum::SYSTEM_REASSIGNED : AssignmentTypeEnum::SYSTEM_ASSIGNED) : null,
    //             'renewal_batch' => $data['batch'] ?? null,
    //             'renewal_batch_id' => null,
    //             // 'additional_notes' => $data['notes'],
    //         ];

    //         if ($isQuoteTypeCar) {
    //             $quoteData['dob'] = (! empty($data['dob'])) ? $this->formatDate($data['dob']) : null;
    //             $quoteData['car_type_insurance_id'] = $carTypeOfInsurance->id ?? null;
    //             $quoteData['claim_history_id'] = $claimHistory->id ?? null;
    //             $quoteData['nationality_id'] = $nationality->id ?? null;
    //             $quoteData['emirate_of_registration_id'] = $emirate->id ?? null;
    //             $quoteData['uae_license_held_for_id'] = $uaeLicenseHeldFor->id ?? null;
    //             $quoteData['car_value'] = $data['car_value'];
    //             $quoteData['car_value_tier'] = $data['car_value'];
    //             $quoteData['renewal_batch'] = $data['batch'];
    //             $quoteData['renewal_batch_id'] = null;
    //             $quoteData['car_make_id'] = $carMake->id ?? null;
    //             $quoteData['car_model_id'] = $carModel->id ?? null;
    //             $quoteData['vehicle_category'] = $vehicleType->category ?? null;
    //             $quoteData['year_of_manufacture'] = $data['year'] ?? null;
    //             $quoteData['previous_advisor_id'] = ! empty($previousAdvisor) ? $previousAdvisor->name : '';
    //             $quoteData['has_ncd_supporting_documents'] = $data['nc_letter'];
    //         }

    //         $quoteData = $this->getNonEmptyValues($quoteData);
    //         $isQuoteTypeCar && $quoteData['is_gcc_standard'] = $data['is_gcc'] == 'Yes' ? 1 : 0;

    //         /*
    //          * API refresh plans when quote_updated_at have latest date
    //          */
    //         if (! $renewalUploadLead->skip_plans) {
    //             $quoteData['quote_updated_at'] = Carbon::now();
    //         }
    //         if ($isQuoteTypeCar) {
    //             if (! empty($carModel) && ($carModelDetail = CarModelDetail::active()
    //                 ->where('is_default', 1)
    //                 ->where('car_model_id', $carModel->id)
    //                 ->first())) {
    //                 $quoteData['cylinder'] = $carModelDetail->cylinder;
    //                 $quoteData['seat_capacity'] = $carModelDetail->seating_capacity;
    //                 $quoteData['vehicle_type_id'] = $carModelDetail->vehicle_type_id;
    //             }

    //             if ($renewalUploadLead->skip_plans == 2 && $data['make'] == GenericRequestEnum::MOTOR_BIKE) {
    //                 $quoteData['vehicle_type_id'] = VehicleType::where('text', GenericRequestEnum::BIKE)->first()->id ?? null;
    //             }

    //             $quoteData['vehicle_type_id'] = ! empty($data['vehicle_type_id'] ?? '') ? $data['vehicle_type_id'] : ($quoteData['vehicle_type_id'] ?? null);

    //             if ($quoteType->code == quoteTypeCode::Car && ! empty($data['year_of_first_registration'])) {
    //                 $quoteData['year_of_first_registration'] = $data['year_of_first_registration'];
    //             } elseif ($quoteType->code == quoteTypeCode::Car && ! empty($data['year'])) {
    //                 $quoteData['year_of_first_registration'] = $data['year'];
    //             }

    //             if (! empty($data['plan_type']) && in_array($data['plan_type'], [CarPlanType::TPL, CarPlanType::COMP])) {
    //                 $quoteData['current_insurance_status'] = 'ACTIVE_'.$data['plan_type'];
    //             }

    //             if (in_array($quoteType->code, [quoteTypeCode::Car, quoteTypeCode::Bike]) && ($insurer = $this->insuranceProviderService->getProviderByCode($data['insurer']))) {
    //                 $quoteData['currently_insured_with'] = $insurer->text;
    //             }
    //         }

    //         LoggerService::info($logPrefix.' quote data setup to update for UUID: '.$quote->uuid);

    //         $quote->update($quoteData);

    //         // create or update in lob specific table i.e home_quote_request, health_quote_request etc.
    //         if ($isQuoteTypeHome) {
    //             $quoteData['personal_quote_id'] = $quote->id;
    //             $quoteData['insurance_provider_id'] = $homeCurrentInsuranceProvider;
    //             $quoteData['possession_type_id'] = $homePossessionTypeId;
    //             $quoteData['accommodation_type_id'] = $homeIliveinAccommodationTypeId;
    //             $quoteData['owner_occupancy_type_id'] = $homeOwnerOccupancyTypeId;
    //             $quoteData['sub_area_id'] = $homeSubAreaId;
    //             $quoteData['coverage_type_id'] = $homeCoverageTypeId;
    //             $quoteData['contents_value_id'] = $homeContents;
    //             $quoteData['personal_belongings_value_id'] = $homePersonalBelongings;
    //             $quoteData['building_value'] = $homeBuildingAed;
    //             $quoteData['building_aed'] = $homeBuildingAed;
    //             $quoteData['renewal_upload_insurance_provider_id'] = $homeInsuranceProvider;
    //             $quoteData['renewal_upload_plan_code'] = $homePlanName;
    //             $quoteData['has_claimed_losses'] = $homeClaimsHistory;
    //             $quoteData['renewal_upload_renewal_premium'] = $homePremium;
    //             $quoteData['insurer_quote_number'] = $homeInsurerQuoteNumber;
    //             $quoteData['previous_advisor_id'] = $homePreviousAdvisorId;
    //             $quoteData['additional_notes'] = $data['notes'];

    //             $homeQuote = HomeQuote::updateOrCreate(
    //                 [
    //                     'uuid' => $quote->uuid,
    //                 ],
    //                 $quoteData
    //             );

    //             if ($homeQuote) {
    //                 LoggerService:info('fn: updateQuote - Home Quote Created/Update', [
    //                     'ref-id' => $quote->uuid,
    //                 ]);
    //             }
    //         }

    //         if (! checkPersonalQuotes($quoteType->code)) {
    //             $this->syncQuote($quote, $quoteData);
    //         }

    //         LoggerService::info($logPrefix.' quote updated UUID: '.$quote->uuid);

    //         if (! empty($advisorId) && $quote->advisor_id != $advisorId) {
    //             $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
    //             LoggerService::info($logPrefix.' quote advisor assigned datetime updated UUID: '.$quote->uuid);
    //         } else {
    //             if ($renewalUploadLead->is_sic == 1) {
    //                 // add entry to quote tag as SIC
    //                 $quoteTagPayload = [
    //                     'name' => QuoteSegmentEnum::SIC->tag(),
    //                     'quote_type_id' => QuoteTypeId::Car,
    //                     'value' => 1,
    //                     'quote_uuid' => $quote->uuid,
    //                 ];

    //                 $checkExisted = QuoteTag::where('quote_uuid', $quote->uuid)->where('name', QuoteSegmentEnum::SIC->tag())->first();
    //                 ! $checkExisted && QuoteTag::create($quoteTagPayload);
    //                 // processing the SIC workflow trigger only and don't send OCB email
    //                 SendCarOCBIntroEmailJob::dispatch($quote->uuid, $previousAdvisor, true, true);
    //                 LoggerService::info($logPrefix.' Quote Tag created. : '.QuoteSegmentEnum::SIC->tag().' for UUID: '.$quote->uuid);
    //             }
    //         }

    //         // mark all other fetch plans pending records as outdated, it will help to target unique records during fetch plans process
    //         RenewalQuoteProcess::where([
    //             'quote_id' => $quote->id,
    //             'status' => RenewalProcessStatuses::PROCESSED,
    //             'type' => RenewalsUploadType::UPDATE_LEADS,
    //             'fetch_plans_status' => FetchPlansStatuses::PENDING,
    //         ])->update(['fetch_plans_status' => FetchPlansStatuses::OUTDATED]);

    //         // mark renewal quote process as processed and assign quote id
    //         $renewalQuoteProcess->update([
    //             'status' => RenewalProcessStatuses::PROCESSED,
    //             'quote_id' => $quote->id,
    //             'fetch_plans_status' => FetchPlansStatuses::PENDING,
    //         ]);

    //         RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);
    //         LoggerService::info($logPrefix.' quoted updated completed for UUID: '.$quote->uuid);

    //         return $quote;
    //     });

    //     return $quote;
    // }

    
    // 
    
    /*
        --------------------------------------
        Fetch plans Section
        --------------------------------------
    */
    public function fetchRenewalPlans(RenewalStatusProcess $renewalStatusProcess, $batch)
    {

        $logPrefix = get_class($this).' FN: fetchRenewalPlans - ';
        
        LoggerService::info($logPrefix.'  Fetch plans started for Home Renewals', extra: [
            'batch' => $batch,
        ]);

        $userId = $renewalStatusProcess->user_id;

        try {
            $jobs = null;
            $totalSkipped = 0;

            $query = RenewalQuoteProcess::where([
                'status' => RenewalProcessStatuses::PROCESSED,
                'quote_type' => QuoteTypeShortCode::HOM,
                'renewal_batch_id' => $batch,
                'type' => RenewalsUploadType::UPDATE_LEADS,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ])->with(['renewalUploadLead', 'personalQuote']);

            $query->chunkById(50, function ($leads) use ($renewalStatusProcess, &$jobs, $logPrefix, &$totalSkipped) {
                foreach ($leads as $lead) {
                    if (! $lead->renewalUploadLead->skip_plans) {
                        
                        $jobs[] = new FetchPlansForHomeRenewalsQuoteJob($lead, $renewalStatusProcess);

                    } else {
                        LoggerService::info($logPrefix.' skipping fetch plans for uuid : '.$lead->personalQuote->uuid);
                        $lead->update(['status' => RenewalProcessStatuses::PLANS_FETCHED, 'fetch_plans_status' => FetchPlansStatuses::FETCHED]);
                        $totalSkipped++;
                    }
                }
            });

            if ($totalSkipped > 0) {
                $renewalStatusProcess->update(['total_completed' => $totalSkipped]);
                LoggerService::info($logPrefix.' total leads for skipped plans ('.$totalSkipped.')');
            }

            if (! empty($jobs)) {
                LoggerService::info($logPrefix.' '.count($jobs).' found to schedule for fetch plans');

                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalStatusProcess, $batch, $userId) {
                        LoggerService::info($logPrefix.' all jobs completed successfully');
                        $renewalStatusProcess->update(['status' => ProcessStatusCode::COMPLETED]);

                        dispatch(function () use ($batch, $userId) {
                            app(self::class)->scheduleHomeRenewalsOcbEmails($batch, $userId);
                        })->onQueue('renewals');
                    })
                    ->catch(function () use ($logPrefix, $renewalStatusProcess) {
                        LoggerService::info($logPrefix.' one of batch is failed. ');
                        $renewalStatusProcess->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function ($batch) use ($logPrefix) {
                        LoggerService::info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(1)
                    ->dispatch();

                LoggerService::info($logPrefix.' all jobs are scheduled');
            } else {
                LoggerService::info($logPrefix.' no leads available for fetch plans, about to mark status as completed');
                $renewalStatusProcess->update(['status' => ProcessStatusCode::COMPLETED]);
                LoggerService::info($logPrefix.' fetch plans is completed');
            }

            return true;
        } catch (\Exception $exception) {
            LoggerService::error($logPrefix.'Fetch plans failed.  Error: '.$exception->getMessage());
            $renewalStatusProcess->update(['status' => ProcessStatusCode::FAILED]);
        }
    }
    
    public function fetchPlans(RenewalQuoteProcess $renewalQuoteProcess, RenewalStatusProcess $renewalStatusProcess)
    {
        $leadData = (object) $renewalQuoteProcess->data;
        
        $quote = PersonalQuote::where('id', $renewalQuoteProcess->quote_id)->first();
        
        LoggerService::startQuoteLogging($quote); 
        
        $logPrefix = get_class($this).' FN: fetchPlans';

        LoggerService::info("$logPrefix  - Fetching plans For Home Renewal Quote");

        if ($quote) {

            LoggerService::info("$logPrefix  - Renewal Home Quote Found"); 

            /* create manual plan if insurance provider, plan and premium is available */
            if (! empty($leadData->insurance_provider) && ! empty($leadData->plan_name) && ! empty($leadData->premium)) {

                LoggerService::info("$logPrefix  - Creating Renewal manual plan for Home Renewal Quote", [
                    'quoteUID' => $quote->uuid,
                ]);

                $createManualPlan = app(HomeQuoteService::class)->createRenewalPlan($quote->uuid, $renewalQuoteProcess->data);

                if (is_int($createManualPlan) && $createManualPlan == 200) {
                    LoggerService::info("$logPrefix  - plan created successfully", extra: [
                    'statusCode' => $createManualPlan
                ]);

                } else {
                    $error = (is_string($createManualPlan)) ? ('Error: '.$createManualPlan) : '';

                    if (isset($createManualPlan->message)) {
                        $error = 'Error: '.$createManualPlan->message;
                    }

                    LoggerService::error("$logPrefix  - plan creation failed. fetch plans skipped UUID: $quote->uuid", extra:[
                        'error' => $error, 
                        'statusCode' => $createManualPlan
                    ]);

                    RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);

                    return false;
                }

            }

            // fetch plans
            
            $plansResponse = app(HomeQuoteService::class)->getQuotePlans($quote->uuid, [
                'getLatestRating' => true,
            ]);

            if ($plansResponse) {
                LoggerService::info('FN: fetchPlans'.' Plans Fetched for Home Renewal Completed..');

                // update status to plans fetched
                $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PLANS_FETCHED, 'fetch_plans_status' => FetchPlansStatuses::FETCHED]);
                RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_completed' => DB::raw('total_completed+1')]);
            } else {
                LoggerService::info('Non Motors FetchPlans FN: fetchHomeQuotePlans'.' Failed to fetch plans for quoteType: '.$renewalQuoteProcess->quote_type.' UUID: '.$quote->uuid.' Error: '.(is_string($plansResponse)) ? $plansResponse : json_encode($plansResponse));
                RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
            }
        } else {
            LoggerService::info('Non Motors FetchPlans FN: fetchHomeQuotePlans QuoteId not found for leadId: '.$renewalQuoteProcess->id.' PolicyNumber: '.$renewalQuoteProcess->policy_number);
            RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
        }
    }


    /*
        --------------------------------------
        Send OCB Email for renewals 
        --------------------------------------
    */

    public function scheduleHomeRenewalsOcbEmails(int $batch, $userId = null):bool
    {
        LoggerService::info('fn: scheduleHomeRenewalsOcbEmails - Renewal OCB Email Send Started', extra: [
            'batch' => $batch,
        ]);

        // get pending leads
        $totalLeads = $this->getPendingOcbLeadsCount($batch);

        // If there are not leads, return false
        if ($totalLeads == 0) {
            LoggerService::info('fn: scheduleHomeRenewalsOcbEmails - No leads found for sending OCB Emails',extra:[
                'batch' => $batch,
            ]);
            return false;
        }

        $renewalsBatchEmail = RenewalsBatchEmails::create([
            'renewal_batch_id' => $batch,
            'status' => ProcessStatusCode::PENDING,
            'total_leads' => $totalLeads,
            'total_sent' => 0,
            'total_bounced' => 0,
            'total_failed' => 0,
            'created_by_id' => $userId,
        ]);

        ScheduleHomeRenewalOcbEmails::dispatch($batch, $renewalsBatchEmail);

        return true;

    }

    public function scheduleHomeOCB(int $batch, RenewalsBatchEmails $renewalsBatchEmail)
    {
        $logPrefix = get_class($this).' FN: scheduleHomeOCB';

        LoggerService::info($logPrefix.' Scheduling Home Renewals OCB email', extra: [
            'batch' => $batch,
        ]);


        try {

            $jobs = [];

            $this->getPendingOcbLeads($batch)
                ->chunkById(50, function ($leads) use (&$jobs, $batch, $renewalsBatchEmail) {
                    foreach ($leads as $lead) {
                        $jobs[] = new HomeRenewalBatchEmailJob($batch, $renewalsBatchEmail, $lead);
                    }
                });

            if ($jobs != null && count($jobs)) {
                LoggerService::info($logPrefix.'total leads to be scheduled for OCB : '.count($jobs));
                Haystack::build()
                    ->onQueue('renewals')
                    ->addJobs($jobs)
                    ->then(function () use ($logPrefix, $renewalsBatchEmail) {
                        LoggerService::info($logPrefix.' all jobs completed successfully');
                        $renewalsBatchEmail->update(['status' => ProcessStatusCode::COMPLETED]);

                    })
                    ->catch(function () use ($logPrefix, $renewalsBatchEmail) {
                        LoggerService::info($logPrefix.' one of batch is failed. ');
                        $renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' everything done');
                    })
                    ->allowFailures()
                    ->withDelay(1)
                    ->dispatch();
            } else {
                LoggerService::info($logPrefix.' No leads to schedule OCB email');
                $renewalsBatchEmail->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            LoggerService::error($logPrefix.' one of batch is failed. Exception : '.$exception->getMessage());
            $renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    private function getPendingOcbLeads(int $batch): object
    {
        $query = RenewalQuoteProcess::select('id', 'quote_id')->where([
            'quote_type' => QuoteTypeShortCode::HOM,
            'renewal_batch_id' => $batch,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::PLANS_FETCHED,
            'email_sent' => 0,
            'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        ]);

        $query->whereHas('personalQuote', function ($q) {
            $q->whereNull('paid_at');
        });

        return $query->groupBy('quote_id'); 
    }

    private function getPendingOcbLeadsCount(int $batch): int
    {
        return $this->getPendingOcbLeads($batch)->count();
    }


}
