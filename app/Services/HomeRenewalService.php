<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\CoverageTypeEnum;
use App\Enums\FetchPlansStatuses;
use App\Enums\LeadSourceEnum;
use App\Enums\LookupsEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RangeLookupKeyEnums;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Jobs\HomeUpdateRenewalQuotesJob;
use App\Jobs\Renewals\FetchPlansForHomeRenewalsQuoteJob;
use App\Jobs\Renewals\HomeRenewalBatchEmailJob;
use App\Jobs\ScheduleHomeRenewalOcbEmails;
use App\Models\InsuranceProvider;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\RangeLookup;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsBatchEmails;
use App\Models\RenewalStatusProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\SubArea;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class HomeRenewalService extends RenewalsUploadService
{
    public function updateQuotesHome(int $renewalsUploadLeadId)
    {
        $renewalsUploadLead = RenewalsUploadLeads::find($renewalsUploadLeadId);

        $logPrefix = get_class($this).' fn: updateQuotes ';
        LoggerService::info($logPrefix.' Quote update started');

        try {
            $jobs = [];

            RenewalQuoteProcess::where([
                'renewals_upload_lead_id' => $renewalsUploadLead->id,
                'status' => RenewalProcessStatuses::VALIDATED,
            ])->chunkById(50, function ($leads) use (&$jobs) {
                foreach ($leads as $lead) {
                    $jobs[] = new HomeUpdateRenewalQuotesJob($lead->id);
                }
            });

            if ($jobs != null && count($jobs)) {

                Bus::batch($jobs)
                    ->then(function () use ($logPrefix, $renewalsUploadLead) {
                        LoggerService::info($logPrefix.' all quotes updated successfully');
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($logPrefix, $renewalsUploadLead) {
                        // Bus batch failed
                        LoggerService::info($logPrefix.' batch failed for updateQuotes . '.$e->getMessage());
                        $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.'  everything done on updating quotes');
                    })
                    ->allowFailures()
                    ->onQueue('renewals')
                    ->dispatch();

                LoggerService::info($logPrefix.' jobs dispatched');
            } else {
                LoggerService::info($logPrefix.' no jobs to create quotes');
                $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            LoggerService::error('BATCH: failed for updateQuotes. Exception', exception: $exception);
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    public function updateQuoteHome(int $renewalQuoteProcessId)
    {
        $renewalQuoteProcess = RenewalQuoteProcess::find($renewalQuoteProcessId);

        $logPrefix = get_class($this).' FN: updateQuote';
        $data = $renewalQuoteProcess->data;

        $quote = DB::transaction(function () use ($renewalQuoteProcess, $data, $logPrefix) {

            $quote = PersonalQuote::where('previous_quote_policy_number', $data['policy_number'])
                ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
                ->where('previous_policy_expiry_date', $this->formatDate($data['end_date']))->first();

            LoggerService::startQuoteLogging($quote->uuid);

            $renewalUploadLead = RenewalsUploadLeads::where('id', $renewalQuoteProcess->renewals_upload_lead_id)->first();

            LoggerService::info($logPrefix.' update quote started', extra: [
                'policyNumber' => $data['policy_number'],
                'id' => $renewalQuoteProcess->id,
                'uploadLeadId' => $renewalUploadLead->id,
            ]);

            if (! $quote) {
                LoggerService::info($logPrefix.' Quote not found', [
                    'policyNumber' => $data['policy_number'],
                    'endDate' => $data['end_date'],
                    'batch' => $renewalQuoteProcess->batch,
                ]);

                return null;
            }

            LoggerService::info($logPrefix.' quote found to update');

            $newAdvisorId = app(RenewalsAddonServices::class)->getUserInfo($data['advisor']);

            $advisorId = $quote->advisor_id == null ? $newAdvisorId : $quote->advisor_id;

            $customerData = $this->buildCustomerData($data);

            $this->updateCustomer($quote, $customerData);

            $quoteData = $this->preparePersonalQuoteData($data, $quote, $advisorId, $renewalQuoteProcess, $customerData);

            LoggerService::info($logPrefix.' quote data setup to update');

            // update in personal quotes
            $quote->update($quoteData);

            // update in home quotes
            $this->createHomeQuoteData($quoteData, $data);

            unset($quoteData['notes']);

            $homeQuote = $quote->homeQuote()->updateOrCreate(
                [
                    'uuid' => $quote->uuid,
                ],
                $quoteData
            );

            if ($homeQuote) {
                LoggerService::info("$logPrefix - Home Quote Created/Update");
            }

            LoggerService::info($logPrefix.' quote updated');

            if (! empty($advisorId) && $quote->advisor_id != $advisorId) {
                $this->updateAdvisorAssignedDateTime(QuoteTypes::HOME, $quote->id, $renewalUploadLead->created_by_id, $advisorId);
                LoggerService::info($logPrefix.' quote advisor assigned datetime updated UUID: '.$quote->uuid);
            }

            // mark all other fetch plans pending records as outdated, it will help to target unique records during fetch plans process
            $this->markAsOutdated($quote);

            // mark renewal quote process as processed and assign quote id
            $this->markAsProcessed($renewalQuoteProcess, $quote);

            RenewalsUploadLeads::where('id', $renewalUploadLead->id)->update(['good' => DB::raw('good+1')]);
            LoggerService::info($logPrefix.' quoted updated completed for UUID: '.$quote->uuid);

            return $quote;
        });

        return $quote;
    }

    /*
        --------------------------------------
        Fetch plans Section
        --------------------------------------
    */
    public function fetchRenewalPlansHome(int $renewalStatusProcessId, int $batch)
    {

        $renewalStatusProcess = RenewalStatusProcess::find($renewalStatusProcessId);

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

                        $jobs[] = new FetchPlansForHomeRenewalsQuoteJob($lead->id, $renewalStatusProcess->id);

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

                Bus::batch($jobs)
                    ->then(function () use ($logPrefix, $renewalStatusProcess, $batch, $userId) {
                        LoggerService::info($logPrefix.' all Renewal Plans Fetched successfully');
                        $renewalStatusProcess->update(['status' => ProcessStatusCode::COMPLETED]);

                        dispatch(function () use ($batch, $userId) {
                            app(self::class)->scheduleHomeRenewalsOcbEmails($batch, $userId);
                        })->onQueue('renewals');
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($logPrefix, $renewalStatusProcess) {
                        LoggerService::info($logPrefix.' batch failed for fetchRenewalPlans. '.$e->getMessage());
                        $renewalStatusProcess->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' everything done on fetching plans');
                    })
                    ->allowFailures()
                    ->onQueue('renewals')
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

    public function fetchPlansHome(int $renewalQuoteProcessId, int $renewalStatusProcessId)
    {
        $renewalQuoteProcess = RenewalQuoteProcess::find($renewalQuoteProcessId);
        $renewalStatusProcess = RenewalStatusProcess::find($renewalStatusProcessId);

        $leadData = (object) $renewalQuoteProcess->data;

        $quote = PersonalQuote::where('id', $renewalQuoteProcess->quote_id)->first();

        LoggerService::startQuoteLogging($quote);

        $logPrefix = get_class($this)." FN: fetchPlans  Renewals Process ID: {$renewalQuoteProcessId } | Renewal Status ID: {$renewalStatusProcessId}";

        LoggerService::info("$logPrefix  - Fetching plans For Home Renewal Quote");

        if ($quote) {

            LoggerService::info("$logPrefix  - Renewal Home Quote Found");

            /* create manual plan if insurance provider, plan and premium is available */
            if (! empty($leadData->insurance_provider) && ! empty($leadData->plan_name) && ! empty($leadData->premium)) {

                LoggerService::info("$logPrefix  - Creating Renewal manual plan for Home Renewal Quote", [
                    'quoteUID' => $quote->uuid,
                ]);

                $this->createManualPlan($quote, $renewalStatusProcess, $renewalQuoteProcess);

            }

            // fetch plans
            $plansResponse = $this->getPlans($quote->uuid);

            if ($plansResponse === true) {
                LoggerService::info('FN: fetchPlans'.' Plans Fetched for Home Renewal Completed..');

                // update status to plans fetched
                $renewalQuoteProcess->update(['status' => RenewalProcessStatuses::PLANS_FETCHED, 'fetch_plans_status' => FetchPlansStatuses::FETCHED]);
                RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_completed' => DB::raw('total_completed+1')]);
            } else {
                LoggerService::info('Non Motors FetchPlans FN: fetchHomeQuotePlans'.' Failed to fetch plans for quoteType: '.$renewalQuoteProcess->quote_type.' UUID: '.$quote->uuid.' Error: '.(is_string($plansResponse)) ? $plansResponse : json_encode($plansResponse));
                $this->updateTotalFailed($renewalStatusProcess);
            }
        } else {
            LoggerService::info('Non Motors FetchPlans FN: fetchHomeQuotePlans QuoteId not found for leadId: '.$renewalQuoteProcess->id.' PolicyNumber: '.$renewalQuoteProcess->policy_number);
            $this->updateTotalFailed($renewalStatusProcess);
        }
    }
    public function getPlans($uuid)
    {
        $quotePlans = app(HomeQuoteService::class)->getQuotePlans($uuid, [
            'getLatestRating' => true,
        ]);
    
        if (isset($quotePlans->quotes)) {
            return true;
        }

        if (! empty($quotePlans->message)) {
            return $quotePlans->message;
        }

        return $quotePlans;
    }

    /*
        --------------------------------------
        Send OCB Email for renewals
        --------------------------------------
    */

    public function scheduleHomeRenewalsOcbEmails(int $batch, $userId = null): bool
    {

        $logPrefix = get_class($this).' FN: scheduleHomeRenewalsOcbEmails';

        LoggerService::info($logPrefix.' Renewal OCB Email Send Started', extra: [
            'batch' => $batch,
        ]);

        // get pending leads
        $totalLeads = $this->getPendingOcbLeadsTotalNonMotor($batch, QuoteTypeShortCode::HOM);

        // If there are not leads, return false
        if ($totalLeads == 0) {
            LoggerService::info($logPrefix.' No leads found for sending OCB Emails', extra: [
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

        ScheduleHomeRenewalOcbEmails::dispatch($batch, $renewalsBatchEmail->id);

        LoggerService::info($logPrefix.' OCB Email Send Started', extra: [
            'batch' => $batch,
        ]);

        return true;
    }

    public function scheduleHomeOCB(int $batch, int $renewalsBatchEmailId)
    {
        $renewalsBatchEmail = RenewalsBatchEmails::find($renewalsBatchEmailId);

        $renewalsBatchEmail->update(['status' => ProcessStatusCode::IN_PROGRESS]);

        $logPrefix = get_class($this).' FN: scheduleHomeOCB';

        LoggerService::info($logPrefix.' Scheduling Home Renewals OCB email', extra: [
            'batch' => $batch,
        ]);

        try {

            $jobs = [];

            $this->getOcbLeadsQueryNonMotor($batch, QuoteTypeShortCode::HOM)
                ->chunkById(50, function ($leads) use (&$jobs, $batch, $renewalsBatchEmail) {
                    foreach ($leads as $lead) {
                        $jobs[] = new HomeRenewalBatchEmailJob($batch, $renewalsBatchEmail->id, $lead->id);
                    }
                });

            if ($jobs != null && count($jobs)) {
                LoggerService::info($logPrefix.'total leads to be scheduled for OCB : '.count($jobs));

                Bus::batch($jobs)
                    ->then(function () use ($logPrefix, $renewalsBatchEmail) {
                        LoggerService::info($logPrefix.' all OCB Emails Sent successfully');
                        $renewalsBatchEmail->update(['status' => ProcessStatusCode::COMPLETED]);

                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($logPrefix, $renewalsBatchEmail) {
                        LoggerService::info($logPrefix.' batch failed for scheduleHomeOCB. '.$e->getMessage());
                        $renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
                    })
                    ->finally(function () use ($logPrefix) {
                        LoggerService::info($logPrefix.' everything done on sending OCB emails');
                    })
                    ->allowFailures()
                    ->onQueue('renewals')
                    ->dispatch();
            } else {
                LoggerService::info($logPrefix.' No leads to schedule OCB email');
                $renewalsBatchEmail->update(['status' => ProcessStatusCode::COMPLETED]);
            }
        } catch (\Exception $exception) {
            LoggerService::error($logPrefix.' batch failed for scheduleHomeOCB. Exception : '.$exception->getMessage());
            $renewalsBatchEmail->update(['status' => ProcessStatusCode::FAILED]);
        }
    }

    private function preparePersonalQuoteData(array $data, PersonalQuote $quote, ?int $advisorId, RenewalQuoteProcess $renewalQuoteProcess, array $customerData): array
    {
        $isReAssignment = $quote->advisor_id != $advisorId;

        $assignmentType = null;
        if ($advisorId) {
            $assignmentType = $isReAssignment ? AssignmentTypeEnum::SYSTEM_REASSIGNED : AssignmentTypeEnum::SYSTEM_ASSIGNED;
        }

        $quoteData = [
            'previous_policy_expiry_date' => (! empty($data['end_date'])) ? $this->formatDate($data['end_date']) : null,
            'advisor_id' => $advisorId,
            'assignment_type' => $assignmentType,
            'renewal_batch_id' => $renewalQuoteProcess->renewal_batch_id,
            'notes' => $data['notes'],
            'insurer_quote_number' => (! empty($data['insurer_quote_no'])) ? $data['insurer_quote_no'] : null,
        ];

        if (! empty($customerData['first_name'])) {
            $quoteData['first_name'] = $customerData['first_name'];
        }

        if (! empty($customerData['last_name'])) {
            $quoteData['last_name'] = $customerData['last_name'];
        }

        if (! empty($customerData['email'])) {
            $quoteData['email'] = $customerData['email'];
        }

        if (! empty($customerData['mobile_no'])) {
            $quoteData['mobile_no'] = $customerData['mobile_no'];
        }

        if (! empty($data['start_date'])) {
            $quoteData['previous_policy_start_date'] = $this->formatDate($data['start_date']);
        }

        return $quoteData;
    }

    private function markAsOutdated(PersonalQuote $quote)
    {
        LoggerService::info('fn: markAsOutdated - marking all fetch plans as outdated');

        // mark all other fetch plans pending records as outdated, it will help to target unique records during fetch plans process
        return RenewalQuoteProcess::where([
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
        ])->update(['fetch_plans_status' => FetchPlansStatuses::OUTDATED]);
    }

    private function createManualPlan(PersonalQuote $quote, RenewalStatusProcess $renewalStatusProcess, RenewalQuoteProcess $renewalQuoteProcess)
    {

        $logPrefix = get_class($this).' FN: createManualPlan';

        $createManualPlan = app(HomeQuoteService::class)->createRenewalPlan($quote->uuid, $renewalQuoteProcess->data);

        if (is_int($createManualPlan) && $createManualPlan == 200) {
            LoggerService::info("$logPrefix  - plan created successfully", extra: [
                'statusCode' => $createManualPlan,
            ]);

        } else {
            $error = (is_string($createManualPlan)) ? ('Error: '.$createManualPlan) : '';

            if (isset($createManualPlan->message)) {
                $error = 'Error: '.$createManualPlan->message;
            }

            LoggerService::error("$logPrefix  - plan creation failed. fetch plans skipped UUID: $quote->uuid", extra: [
                'error' => $error,
                'statusCode' => $createManualPlan,
            ]);

            $this->updateTotalFailed($renewalStatusProcess);

            return false;
        }

        return true;
    }

    private function markAsProcessed(RenewalQuoteProcess $renewalQuoteProcess, PersonalQuote $quote)
    {
        LoggerService::info('fn: markAsProcessed - marking renewal quote process as processed and assign quote id');

        return $renewalQuoteProcess->update([
            'status' => RenewalProcessStatuses::PROCESSED,
            'quote_id' => $quote->id,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
        ]);
    }

    private function createHomeQuoteData(array &$quoteData, array $data): array
    {
        $quoteData['insurance_provider_id'] = (! empty($data['current_insurance_provider'])) ? InsuranceProvider::whereRaw('LOWER(code) = ?', [strtolower(trim($data['current_insurance_provider']))])->first()->id : null;
        $quoteData['possession_type_id'] = (! empty($data['you_are_a'])) ? Lookup::whereRaw('LOWER(text) = ?', [strtolower(trim($data['you_are_a']))])->where('key', LookupsEnum::POSSESSION_TYPE)->first()->id : null;
        $quoteData['accommodation_type_id'] = (! empty($data['i_live_in_a'])) ? Lookup::whereRaw('LOWER(text) = ?', [strtolower(trim($data['i_live_in_a']))])->where('key', LookupsEnum::ACCOMMODATION_TYPE)->first()->id : null;
        $quoteData['owner_occupancy_type_id'] = (! empty($data['occupancy_status_for_owners'])) ? Lookup::whereRaw('LOWER(text) = ?', [strtolower(trim($data['occupancy_status_for_owners']))])->where('key', LookupsEnum::OWNER_OCCUPANCY_TYPE)->first()->id : null;
        $quoteData['sub_area_id'] = (! empty($data['location_area'])) ? SubArea::whereRaw('LOWER(text) = ?', [strtolower(trim($data['location_area']))])->first()->id : null;
        $quoteData['coverage_type_id'] = (! empty($data['cover_required'])) ? Lookup::whereRaw('LOWER(text) = ?', [strtolower(trim($data['cover_required']))])->where('key', LookupsEnum::COVERAGE_TYPE)->first()->id : null;
        $quoteData['contents_value_id'] = $this->getContentsAed($data);
        $quoteData['personal_belongings_value_id'] = $this->getPersonalBelongingsAed($data);
        $quoteData['building_value'] = $this->getBuildingAed($data);
        $quoteData['building_aed'] = $this->getBuildingAed($data);
        $quoteData['has_claimed_losses'] = (! empty($data['claims_history']) && $data['claims_history'] == 'Yes') ? 1 : 0;
        $quoteData['insurer_quote_number'] = (! empty($data['insurer_quote_no'])) ? $data['insurer_quote_no'] : null;
        $quoteData['previous_advisor_id'] = (! empty($data['previous_advisor_email'])) ? app(RenewalsAddonServices::class)->getUserInfo($data['previous_advisor_email']) : null;
        $quoteData['additional_notes'] = $data['notes'];

        return $quoteData;
    }

    private function getContentsAed($data)
    {

        LoggerService::info('fn: getContentsAed', [
            'cover_required' => $data['cover_required'],
        ]);

        if (trim($data['cover_required']) == CoverageTypeEnum::BUILDING_ONLY->value) {
            return null;
        }

        $homeContents = (! empty($data['contents'])) ? RangeLookup::whereRaw('LOWER(text) = ?', [strtolower(trim($data['contents']))])->where('key', RangeLookupKeyEnums::CONTENT_VALUES)->first()->id : null;
        LoggerService::info('fn: getContentsAed - contents: '.$homeContents);

        return $homeContents;
    }

    private function getPersonalBelongingsAed($data)
    {

        LoggerService::info('fn: getPersonalBelongingsAed', [
            'cover_required' => $data['cover_required'],
        ]);

        $coverRequired = trim($data['cover_required']);

        if ($coverRequired == CoverageTypeEnum::CONTENTS_ONLY->value || $coverRequired == CoverageTypeEnum::BUILDING_ONLY->value || $coverRequired == CoverageTypeEnum::BUILDING_AND_CONTENTS->value) {
            return null;
        }

        $homePersonalBelongings = (! empty($data['personal_belongings'])) ? RangeLookup::whereRaw('LOWER(text) = ?', [strtolower(trim($data['personal_belongings']))])->where('key', RangeLookupKeyEnums::PERSONAL_BELONGING_VALUES)->first()->id : null;
        LoggerService::info('fn: getPersonalBelongingsAed - personal belongings: '.$homePersonalBelongings);

        return $homePersonalBelongings;
    }

    private function getBuildingAed($data)
    {
        LoggerService::info('fn: getBuildingAed', [
            'cover_required' => $data['cover_required'],
        ]);

        $coverRequired = trim($data['cover_required']);

        if ($coverRequired == CoverageTypeEnum::CONTENTS_ONLY->value || $coverRequired == CoverageTypeEnum::CONTENTS_PERSONAL_BELONGINGS->value) {
            return null;
        }
        $homeBuildingAed = (! empty($data['building'])) ? $data['building'] : null;
        LoggerService::info('fn: getBuildingAed - building: '.$homeBuildingAed);

        return $homeBuildingAed;
    }

    private function updateTotalFailed(RenewalStatusProcess $renewalStatusProcess)
    {
        return RenewalStatusProcess::where('id', $renewalStatusProcess->id)->update(['total_failed' => DB::raw('total_failed+1')]);
    }

}
