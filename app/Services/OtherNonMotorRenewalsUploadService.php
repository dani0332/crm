<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\FetchPlansStatuses;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Imports\UploadAndUpdateOtherNonMotorImport;
use App\Jobs\Renewals\ProcessOtherNonMotorRenewal;
use App\Models\PersonalQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class OtherNonMotorRenewalsUploadService
{
    public const QUOTE_TYPE = QuoteTypeShortCode::OTH_NON_MOTOR;

    private array $allowedPersonalQuoteTypeIds;

    public function __construct()
    {
        $this->allowedPersonalQuoteTypeIds = [
            QuoteTypes::YACHT->id(),
            QuoteTypes::PET->id(),
            QuoteTypes::CYCLE->id(),
            QuoteTypes::BIKE->id(),
            QuoteTypes::BUSINESS->id(),
        ];
    }

    /**
     * Process upload & update for "All other non-motor lines"
     */
    public function processUploadUpdate(int $renewalsUploadLeadId): bool
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::NON_MOTOR_UPLOAD_AND_UPDATE);

        $result = false;
        $renewalsUploadLead = RenewalsUploadLeads::find($renewalsUploadLeadId);

        if (! $renewalsUploadLead) {
            LoggerService::warning('Non-motor upload and update lead not found. Lead ID: '.$renewalsUploadLeadId);

            return $result;
        }

        try {
            LoggerService::info('Non-motor upload and update lead found. Lead ID: '.$renewalsUploadLeadId.' uploading update leads in Progress Now');
            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            DB::transaction(function () use ($renewalsUploadLead) {
                $upload = new UploadAndUpdateOtherNonMotorImport($this, $renewalsUploadLead);
                $leadFile = $renewalsUploadLead->file_path;
                $upload->import($leadFile, 'azureIMPrivate');
            });

            $validationFailedCount = RenewalQuoteProcess::where('renewals_upload_lead_id', $renewalsUploadLead->id)
                ->where('status', RenewalProcessStatuses::VALIDATION_FAILED)
                ->count();

            if ($validationFailedCount > 0) {
                $renewalsUploadLead->increment('cannot_upload', $validationFailedCount);
            }

            $validationSuccessCount = RenewalQuoteProcess::where('renewals_upload_lead_id', $renewalsUploadLead->id)
                ->where('status', RenewalProcessStatuses::NEW)
                ->count();

            if ($validationSuccessCount === 0) {
                LoggerService::info('No validated jobs to dispatch for lead: '.$renewalsUploadLead->id);
                $renewalsUploadLead->update([
                    'status' => ProcessStatusCode::COMPLETED,
                    'total_records' => $validationFailedCount,
                ]);
                $result = false;
            } else {
                LoggerService::info('Validated jobs to dispatch for lead: '.$renewalsUploadLead->id);
                $this->dispatchProcessingJobs($renewalsUploadLead);
                $result = true;
            }
        } catch (\Throwable $exception) {
            LoggerService::error('Non-motor upload and update process failed. Lead ID: '.$renewalsUploadLeadId.' Error: '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
            $result = false;
        }

        return $result;
    }

    private function dispatchProcessingJobs(RenewalsUploadLeads $renewalsUploadLead): bool
    {
        $jobs = [];

        LoggerService::info('Dispatching processing jobs for lead: '.$renewalsUploadLead->id);
        RenewalQuoteProcess::where('status', RenewalProcessStatuses::NEW)
            ->where('renewals_upload_lead_id', $renewalsUploadLead->id)
            ->chunkById(100, function ($processes) use (&$jobs, $renewalsUploadLead) {
                foreach ($processes as $process) {
                    $jobs[] = new ProcessOtherNonMotorRenewal($renewalsUploadLead->id, $process->id);
                }
            });

        if (empty($jobs)) {
            LoggerService::info('OTH FN: No validated jobs to dispatch for lead: '.$renewalsUploadLead->id);
            $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);

            return false;
        }

        Bus::batch($jobs)
            ->onQueue('renewals')
            ->name('Other Non Motor Renewals Batch')
            ->then(function () use ($renewalsUploadLead) {
                LoggerService::info('OTH FN: All jobs completed successfully');
                $renewalsUploadLead->refresh();
                $renewalsUploadLead->update([
                    'status' => ProcessStatusCode::COMPLETED,
                    'total_records' => (int) $renewalsUploadLead->good + (int) $renewalsUploadLead->cannot_upload,
                ]);
            })
            ->catch(function () use ($renewalsUploadLead) {
                LoggerService::info('OTH FN: One or more jobs failed');
                $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);
            })
            ->finally(function () use ($renewalsUploadLead) {
                LoggerService::info('OTH FN: Everything done for lead: '.$renewalsUploadLead->id);
            })
            ->allowFailures()
            ->dispatch();

        return true;
    }

    public function processSingle(int $leadId, int $processId): void
    {
        LoggerService::info('Processing single lead: '.$leadId.' process: '.$processId);
        $lead = RenewalsUploadLeads::find($leadId);
        $process = RenewalQuoteProcess::find($processId);

        if (! $lead || ! $process || $process->status !== RenewalProcessStatuses::NEW) {
            LoggerService::info('Single lead not found or process not new. Lead ID: '.$leadId.' Process ID: '.$processId);

            return;
        }

        $this->assignLead($process, $lead);
    }

    private function assignLead(RenewalQuoteProcess $process, RenewalsUploadLeads $renewalsUploadLead): void
    {
        LoggerService::info('Assigning lead: '.$process->id.' to lead: '.$renewalsUploadLead->id);
        $data = Arr::wrap($process->data);
        $refId = isset($data['ref_id']) ? trim($data['ref_id']) : null;
        $advisorEmail = isset($data['advisor_email']) ? strtolower(trim($data['advisor_email'])) : null;

        $advisor = null;
        if (! empty($advisorEmail)) {
            $advisor = User::where('email', $advisorEmail)->first();
        }

        $quote = null;
        if ($refId) {
            $refId = strtoupper(trim((string) $refId));
            $quote = $this->findEligibleQuote($refId);
        }

        LoggerService::startQuoteLogging($refId);

        // Import already enforces business validations (renewal_upload source, not manually assigned).
        // Here we only guard against missing records.
        if (! $advisor || ! $quote) {
            LoggerService::info('Advisor or quote not found. Lead ID: '.$renewalsUploadLead->id.' Process ID: '.$process->id);
            $process->status = RenewalProcessStatuses::BAD_DATA;
            $process->fetch_plans_status = FetchPlansStatuses::OUTDATED;
            $process->save();
            $renewalsUploadLead->increment('cannot_upload');

            return;
        }

        LoggerService::info('Advisor found: '.$advisor->id.' for email: '.$advisorEmail);
        LoggerService::info('Quote found: '.$quote->id.' for ref ID: '.$refId);

        $this->assignAdvisor($quote, $advisor->id);
        LoggerService::info('Advisor assigned: '.$advisor->id.' to quote: '.$quote->id);

        $process->update([
            'quote_type' => self::QUOTE_TYPE,
            'quote_id' => $quote->id,
            'policy_number' => $quote->previous_quote_policy_number,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'status' => RenewalProcessStatuses::PROCESSED,
            'validation_errors' => [],
        ]);

        $renewalsUploadLead->increment('good');
    }

    public function findEligibleQuote(string $refId): ?PersonalQuote
    {
        return PersonalQuote::whereIn('quote_type_id', $this->allowedPersonalQuoteTypeIds)
            ->where(function ($query) use ($refId) {
                $query->where('code', $refId)
                    ->orWhere('uuid', $refId);
            })->first();
    }

    public function isManuallyAssigned(PersonalQuote $quote): bool
    {
        return in_array($quote->assignment_type, [
            AssignmentTypeEnum::MANUAL_ASSIGNED,
            AssignmentTypeEnum::MANUAL_REASSIGNED,
        ]);
    }

    private function assignAdvisor($quote, int $advisorId): void
    {
        $quote->advisor_id = $advisorId;
        $quote->assignment_type = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        $quote->quote_status_id = QuoteStatusEnum::Allocated;
        $quote->save();

        $businessQuote = $quote->businessQuote;
        if ($businessQuote) {
            $businessQuote->advisor_id = $advisorId;
            $businessQuote->assignment_type = AssignmentTypeEnum::SYSTEM_REASSIGNED;
            $businessQuote->quote_status_id = QuoteStatusEnum::Allocated;
            $businessQuote->save();
        }
    }
}
