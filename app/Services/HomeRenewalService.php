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

    public function updateQuote(RenewalQuoteProcess $renewalQuoteProcess)
    {

        $logPrefix = get_class($this).' FN: updateQuote';
        $data = $renewalQuoteProcess->data;

        $quoteTypeCode = QuoteTypeShortCode::HOM;


        $quote = DB::transaction(function () use ($renewalQuoteProcess, $data, $logPrefix, $quoteTypeCode) {
            throw_if(! in_array($quoteTypeCode, [QuoteTypeShortCode::HOM]), 'Only Insurance Type Home allowed to update lead');

            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();

            LoggerService::info($logPrefix.' update quote started for PolicyNo: '.$data['policy_number'].' ID: '.$renewalQuoteProcess->id.' UploadLeadId: '.$renewalUploadLead->id);

            $quoteType = QuoteTypeEnum::HOM;
            
            $quote = PersonalQuote::where('previous_quote_policy_number', $data['policy_number'])
                ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))->first();

            throw_unless($quote, ('Quote not found for PolicyNumber: '.$data['policy_number'].' EndDate: '.$data['end_date'].' Batch: '.$renewalQuoteProcess->batch));

            $newAdvisorId = app(RenewalsAddonService::class)->getUserInfo($data['advisor']);
            $advisorId = $quote->advisor_id == null ? $newAdvisorId : $quote->advisor_id;
            
            $homeCurrentInsuranceProvider = (! empty($data['current_insurance_provider'])) ? InsuranceProvider::where('code', $data['current_insurance_provider'])->first()->id : null;
            $homePossessionTypeId = (! empty($data['you_are_a'])) ? RangeLookup::where('text', $data['you_are_a'])->where('key', RangeLookupKeyEnums::POSSESSION_TYPE)->first()->id : null;
            $homeIliveinAccommodationTypeId = (! empty($data['i_live_in_a'])) ? RangeLookup::where('text', $data['i_live_in_a'])->where('key', RangeLookupKeyEnums::ACCOMMODATION_TYPE)->first()->id : null;
            $homeOwnerOccupancyTypeId = (! empty($data['occupancy_status_for_owners'])) ? RangeLookup::where('text', $data['occupancy_status_for_owners'])->where('key', RangeLookupKeyEnums::OWNER_OCCUPANCY_TYPE)->first()->id : null;
            $homeSubAreaId = (! empty($data['location_area'])) ? SubArea::where('text', $data['location_area'])->first()->id : null;
            $homeCoverageTypeId = (! empty($data['cover_required'])) ? RangeLookup::where('text', $data['cover_required'])->where('key', RangeLookupKeyEnums::COVERAGE_TYPE)->first()->id : null;
            $homeContents = (! empty($data['contents'])) ? RangeLookup::where('text', $data['contents'])->where('key', RangeLookupKeyEnums::CONTENT_VALUES)->first()->id : null;
            $homePersonalBelongings = (! empty($data['personal_belongings'])) ? RangeLookup::where('text', $data['personal_belongings'])->where('key', RangeLookupKeyEnums::PERSONAL_BELONGING_VALUES)->first()->id : null;
            $homeBuildingAed = (! empty($data['building'])) ? $data['building'] : null;
            $homeInsuranceProvider = (! empty($data['insurance_provider'])) ? InsuranceProvider::where('code', $data['insurance_provider'])->first()->id : null;
            $homePlanName = (! empty($data['plan_name'])) ? $data['plan_name'] : null;
            $homeClaimsHistory = (! empty($data['claims_history']) && $data['claims_history'] == 'Yes') ? 1 : 0;
            $homePremium = (! empty($data['premium'])) ? $data['premium'] : null;
            $homeInsurerQuoteNumber = (! empty($data['insurer_quote_no'])) ? $data['insurer_quote_no'] : null;
            $homePreviousAdvisorId = (! empty($data['previous_advisor_email'])) ? $this->renewalsAddonService->getUserInfo($data['previous_advisor_email']) : null;
            


            LoggerService::info($logPrefix.' quote found to update with UUID: '.$quote->uuid);

            $customerData = $this->buildCustomerData($data);

            $isReAssignment = $quote->advisor_id != $advisorId;

            $this->updateCustomer($quote, $customerData);

            $quoteData = [
                'first_name' => $customerData['first_name'],
                'last_name' => $customerData['last_name'],
                'email' => $customerData['email'],
                'mobile_no' => $customerData['mobile_no'],
                'previous_policy_expiry_date' => (! empty($data['end_date'])) ? $this->formatDate($data['end_date']) : null,
                'previous_policy_start_date' => (! empty($data['start_date'])) ? $this->formatDate($data['start_date']) : null,
                'advisor_id' => $advisorId,
                'assignment_type' => $advisorId ? ($isReAssignment ? AssignmentTypeEnum::SYSTEM_REASSIGNED : AssignmentTypeEnum::SYSTEM_ASSIGNED) : null,
                'renewal_batch_id' => null,
                'notes' => $data['notes']
            ];

            LoggerService::info($logPrefix.' quote data setup to update for UUID: '.$quote->uuid);

            $quote->update($quoteData);

            // create anetry in home quote request table 
            $quoteData['personal_quote_id'] = $quote->id;
            $quoteData['insurance_provider_id'] = $homeCurrentInsuranceProvider;
            $quoteData['possession_type_id'] = $homePossessionTypeId;
            $quoteData['accommodation_type_id'] = $homeIliveinAccommodationTypeId;
            $quoteData['owner_occupancy_type_id'] = $homeOwnerOccupancyTypeId;
            $quoteData['sub_area_id'] = $homeSubAreaId;
            $quoteData['coverage_type_id'] = $homeCoverageTypeId;
            $quoteData['contents_value_id'] = $homeContents;
            $quoteData['personal_belongings_value_id'] = $homePersonalBelongings;
            $quoteData['building_value'] = $homeBuildingAed;
            $quoteData['building_aed'] = $homeBuildingAed;
            $quoteData['renewal_upload_insurance_provider_id'] = $homeInsuranceProvider;
            $quoteData['renewal_upload_plan_code'] = $homePlanName;
            $quoteData['has_claimed_losses'] = $homeClaimsHistory;
            $quoteData['renewal_upload_renewal_premium'] = $homePremium;
            $quoteData['insurer_quote_number'] = $homeInsurerQuoteNumber;
            $quoteData['previous_advisor_id'] = $homePreviousAdvisorId;
            $quoteData['additional_notes'] = $data['notes'];

            $homeQuote = HomeQuote::updateOrCreate(
                [
                    'uuid' => $quote->uuid,
                ],
                $quoteData
            );

            if ($homeQuote) {
                LoggerService:info('fn: updateQuote - Home Quote Created/Update', [
                    'ref-id' => $quote->uuid,
                ]);
            }
            


            LoggerService::info($logPrefix.' quote updated UUID: '.$quote->uuid);

            if (! empty($advisorId) && $quote->advisor_id != $advisorId) {
                $this->updateAdvisorAssignedDateTime($quoteType->code, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
                LoggerService::info($logPrefix.' quote advisor assigned datetime updated UUID: '.$quote->uuid);
            } 

            // mark all other fetch plans pending records as outdated, it will help to target unique records during fetch plans process
            RenewalQuoteProcess::where([
                'quote_id' => $quote->id,
                'status' => RenewalProcessStatuses::PROCESSED,
                'type' => RenewalsUploadType::UPDATE_LEADS,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ])->update(['fetch_plans_status' => FetchPlansStatuses::OUTDATED]);

            // mark renewal quote process as processed and assign quote id
            $renewalQuoteProcess->update([
                'status' => RenewalProcessStatuses::PROCESSED,
                'quote_id' => $quote->id,
                'fetch_plans_status' => FetchPlansStatuses::PENDING,
            ]);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);
            LoggerService::info($logPrefix.' quoted updated completed for UUID: '.$quote->uuid);

            return $quote;
        });

        return $quote;
    }

    
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

        $logPrefix = get_class($this).' FN: scheduleHomeRenewalsOcbEmails';
        
        LoggerService::info($logPrefix.' Renewal OCB Email Send Started', extra: [
            'batch' => $batch,
        ]);

        // get pending leads
        $totalLeads = $this->getPendingOcbLeadsCount($batch);

        // If there are not leads, return false
        if ($totalLeads == 0) {
            LoggerService::info($logPrefix.' No leads found for sending OCB Emails',extra:[
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

        LoggerService::info($logPrefix.' OCB Email Send Started', extra: [
            'batch' => $batch,
        ]);

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
        $logPrefix = get_class($this).' FN: getPendingOcbLeads';

        LoggerService::info($logPrefix.' Getting pending OCB leads', extra: [
            'batch' => $batch,
        ]);

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

    private function createHomeQuoteData(RenewalQuoteProcess $renewalQuoteProcess)
    {
        $homeCurrentInsuranceProvider = (! empty($data['current_insurance_provider'])) ? InsuranceProvider::where('code', $data['current_insurance_provider'])->first()->id : null;
        $homePossessionTypeId = (! empty($data['you_are_a'])) ? RangeLookup::where('text', $data['you_are_a'])->where('key', RangeLookupKeyEnums::POSSESSION_TYPE)->first()->id : null;
        $homeIliveinAccommodationTypeId = (! empty($data['i_live_in_a'])) ? RangeLookup::where('text', $data['i_live_in_a'])->where('key', RangeLookupKeyEnums::ACCOMMODATION_TYPE)->first()->id : null;
        $homeOwnerOccupancyTypeId = (! empty($data['occupancy_status_for_owners'])) ? RangeLookup::where('text', $data['occupancy_status_for_owners'])->where('key', RangeLookupKeyEnums::OWNER_OCCUPANCY_TYPE)->first()->id : null;
        $homeSubAreaId = (! empty($data['location_area'])) ? SubArea::where('text', $data['location_area'])->first()->id : null;
        $homeCoverageTypeId = (! empty($data['cover_required'])) ? RangeLookup::where('text', $data['cover_required'])->where('key', RangeLookupKeyEnums::COVERAGE_TYPE)->first()->id : null;
        $homeContents = (! empty($data['contents'])) ? RangeLookup::where('text', $data['contents'])->where('key', RangeLookupKeyEnums::CONTENT_VALUES)->first()->id : null;
        $homePersonalBelongings = (! empty($data['personal_belongings'])) ? RangeLookup::where('text', $data['personal_belongings'])->where('key', RangeLookupKeyEnums::PERSONAL_BELONGING_VALUES)->first()->id : null;
        $homeBuildingAed = (! empty($data['building'])) ? $data['building'] : null;
        $homeInsuranceProvider = (! empty($data['insurance_provider'])) ? InsuranceProvider::where('code', $data['insurance_provider'])->first()->id : null;
        $homePlanName = (! empty($data['plan_name'])) ? $data['plan_name'] : null;
        $homeClaimsHistory = (! empty($data['claims_history']) && $data['claims_history'] == 'Yes') ? 1 : 0;
        $homePremium = (! empty($data['premium'])) ? $data['premium'] : null;
        $homeInsurerQuoteNumber = (! empty($data['insurer_quote_no'])) ? $data['insurer_quote_no'] : null;
        $homePreviousAdvisorId = (! empty($data['previous_advisor_email'])) ? $this->renewalsAddonService->getUserInfo($data['previous_advisor_email']) : null;
        
        
    }


}
