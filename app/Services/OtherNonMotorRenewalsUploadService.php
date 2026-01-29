<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\FetchPlansStatuses;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Imports\UploadAndUpdateOtherNonMotorImport;
use App\Jobs\Renewals\ProcessOtherNonMotorRenewal;
use App\Models\BusinessQuote;
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
    public const QUOTE_TYPE = 'OTH_NON_MOTOR';

    private array $allowedPersonalQuoteTypeIds;

    public function __construct()
    {
        $this->allowedPersonalQuoteTypeIds = [
            QuoteTypes::YACHT->id(),
            QuoteTypes::PET->id(),
            QuoteTypes::BIKE->id(),
            QuoteTypes::CYCLE->id(),
            QuoteTypes::LIFE->id(),
            QuoteTypes::JETSKI->id(),
            QuoteTypes::BUSINESS->id(),
        ];
    }

    /**
     * Process upload & update for "All other non-motor lines"
     */
    public function processUploadUpdate(int $renewalsUploadLeadId): bool
    {
        $renewalsUploadLead = RenewalsUploadLeads::find($renewalsUploadLeadId);

        if (! $renewalsUploadLead) {
            return false;
        }

        $logPrefix = 'OTH FN: processUploadUpdate RenewalLeadId: '.$renewalsUploadLeadId.' FileName: '.$renewalsUploadLead->file_name;

        try {
            LoggerService::info($logPrefix.' uploading update leads in Progress Now');
            $renewalsUploadLead->update(['status' => ProcessStatusCode::IN_PROGRESS]);

            DB::transaction(function () use ($renewalsUploadLead) {
                $upload = new UploadAndUpdateOtherNonMotorImport($this, $renewalsUploadLead);
                $leadFile = $renewalsUploadLead->file_path;
                $upload->import($leadFile, 'azureIM');
            });

            $validationFailedCount = RenewalQuoteProcess::where('renewals_upload_lead_id', $renewalsUploadLead->id)
                ->where('status', RenewalProcessStatuses::VALIDATION_FAILED)
                ->count();

            if ($validationFailedCount > 0) {
                $renewalsUploadLead->increment('cannot_upload', $validationFailedCount);
            }

            $this->dispatchProcessingJobs($renewalsUploadLead);

            return true;
        } catch (\Throwable $exception) {
            LoggerService::error($logPrefix.' uploading updates Process Failed. Error: '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);

            return false;
        }
    }

    private function dispatchProcessingJobs(RenewalsUploadLeads $renewalsUploadLead): void
    {
        $jobs = [];

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

            return;
        }

        Bus::batch($jobs)
            ->onQueue('renewals')
            ->name('Other Non Motor Renewals Batch')
            ->then(function () use ($renewalsUploadLead) {
                LoggerService::info('OTH FN: All jobs completed successfully');
                $renewalsUploadLead->update(['status' => ProcessStatusCode::COMPLETED]);
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
    }

    public function processSingle(int $leadId, int $processId): void
    {
        $lead = RenewalsUploadLeads::find($leadId);
        $process = RenewalQuoteProcess::find($processId);

        if (! $lead || ! $process || $process->status !== RenewalProcessStatuses::NEW) {
            return;
        }

        $this->assignLead($process, $lead);
    }

    private function assignLead(RenewalQuoteProcess $process, RenewalsUploadLeads $renewalsUploadLead): void
    {
        $data = Arr::wrap($process->data);
        $refId = isset($data['ref_id']) ? trim($data['ref_id']) : null;
        $advisorEmail = isset($data['advisor_email']) ? strtolower(trim($data['advisor_email'])) : null;

        $advisor = null;
        if (! empty($advisorEmail)) {
            $advisor = User::where('email', $advisorEmail)->first();
            LoggerService::info('Advisor Email: '.$advisorEmail);
        }

        $quote = null;
        if ($refId) {
            $refId = strtoupper(trim((string) $refId));
            $quote = $this->findEligibleQuote($refId);
        }

        // Import already enforces business validations (renewal_upload source, not manually assigned).
        // Here we only guard against missing records.
        if (! $advisor || ! $quote) {
            $process->status = RenewalProcessStatuses::BAD_DATA;
            $process->fetch_plans_status = FetchPlansStatuses::OUTDATED;
            $process->save();
            $renewalsUploadLead->increment('cannot_upload');

            return;
        }

        $process->update([
            'quote_type' => self::QUOTE_TYPE,
            'quote_id' => $quote->id,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'status' => RenewalProcessStatuses::PROCESSED,
            'validation_errors' => [],
        ]);

        $this->assignAdvisor($quote, $advisor->id);

        $renewalsUploadLead->increment('good');
    }

    public function findEligibleQuote(string $refId)
    {
        return PersonalQuote::whereIn('quote_type_id', $this->allowedPersonalQuoteTypeIds)
            ->where(function ($query) use ($refId) {
                $query->where('code', $refId)
                    ->orWhere('uuid', $refId);
            })->first();
    }

    public function isManuallyAssigned($quote): bool
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
        $quote->save();
    }
}
