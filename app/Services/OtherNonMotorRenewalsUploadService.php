<?php

namespace App\Services;

use App\Enums\AssignmentTypeEnum;
use App\Enums\FetchPlansStatuses;
use App\Enums\LeadSourceEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Imports\UploadAndUpdateOtherNonMotorImport;
use App\Models\BusinessQuote;
use App\Models\PersonalQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OtherNonMotorRenewalsUploadService
{
    public const QUOTE_TYPE = 'OTH_NON_MOTOR';

    private array $allowedPersonalQuoteTypeIds;
    private array $allowedBusinessQuoteTypeIds;

    public function __construct(private QuoteDocumentService $quoteDocumentService)
    {
        $this->allowedPersonalQuoteTypeIds = [
            QuoteTypes::YACHT->id(),
            QuoteTypes::PET->id(),
            QuoteTypes::CYCLE->id(),
        ];

        $this->allowedBusinessQuoteTypeIds = [
            QuoteTypes::CORPLINE->id(),
            QuoteTypes::GROUP_MEDICAL->id(),
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
                $upload = new UploadAndUpdateOtherNonMotorImport($renewalsUploadLead);
                $leadFile = $this->quoteDocumentService->getDocumentUrl($renewalsUploadLead->file_path);
                $upload->import($leadFile);
            });

            $validationFailedCount = RenewalQuoteProcess::where('renewals_upload_lead_id', $renewalsUploadLead->id)
                ->where('status', RenewalProcessStatuses::VALIDATION_FAILED)
                ->count();

            if ($validationFailedCount > 0) {
                $renewalsUploadLead->increment('cannot_upload', $validationFailedCount);
            }

            $this->validateAndAssignLeads($renewalsUploadLead);

            $renewalsUploadLead->refresh()->update(['status' => ProcessStatusCode::COMPLETED]);

            return true;
        } catch (\Throwable $exception) {
            LoggerService::error($logPrefix.' uploading updates Process Failed. Error: '.$exception->getMessage());
            $renewalsUploadLead->update(['status' => ProcessStatusCode::FAILED]);

            return false;
        }
    }

    private function validateAndAssignLeads(RenewalsUploadLeads $renewalsUploadLead): void
    {
        RenewalQuoteProcess::where('status', RenewalProcessStatuses::NEW)
            ->where('renewals_upload_lead_id', $renewalsUploadLead->id)
            ->chunkById(100, function ($processes) use ($renewalsUploadLead) {
                foreach ($processes as $process) {
                    $this->validateAndAssignLead($process, $renewalsUploadLead);
                }
            });
    }

    private function validateAndAssignLead(RenewalQuoteProcess $process, RenewalsUploadLeads $renewalsUploadLead): void
    {
        $leadValidationErrors = collect();
        $data = Arr::wrap($process->data);
        $refId = isset($data['ref_id']) ? trim($data['ref_id']) : null;
        $advisorEmail = isset($data['advisor_email']) ? strtolower(trim($data['advisor_email'])) : null;

        if (empty($refId)) {
            $leadValidationErrors->push('Ref-ID is required');
        }

        $advisor = null;
        if (empty($advisorEmail)) {
            $leadValidationErrors->push('Advisor Email is required');
        } else {
            $advisor = User::where('email', $advisorEmail)->first();
            if (! $advisor) {
                $leadValidationErrors->push('Advisor Email must belong to an active IMCRM user');
            }
        }

        $quote = null;
        if ($refId) {
            $quote = $this->findEligibleQuote($refId);
            if (! $quote) {
                $leadValidationErrors->push('No eligible renewal lead found for provided Ref-ID');
            }
        }

        if ($quote && $quote->source != LeadSourceEnum::RENEWAL_UPLOAD) {
            $leadValidationErrors->push('Lead source must be renewal_upload');
        }

        if ($quote && $this->isManuallyAssigned($quote)) {
            $leadValidationErrors->push('Lead is manually assigned and was not updated');
        }

        if ($leadValidationErrors->count() > 0) {
            $process->validation_errors = $leadValidationErrors->values();
            $process->status = RenewalProcessStatuses::BAD_DATA;
            $process->fetch_plans_status = FetchPlansStatuses::OUTDATED;
            $process->save();
            $renewalsUploadLead->increment('cannot_upload');

            return;
        }

        $process->quote_type = self::QUOTE_TYPE;
        $process->quote_id = $quote->id;
        $process->fetch_plans_status = FetchPlansStatuses::FETCHED;
        $process->status = RenewalProcessStatuses::PROCESSED;
        $process->validation_errors = [];
        $process->save();

        $this->assignAdvisor($quote, $advisor->id);

        $renewalsUploadLead->increment('good');
    }

    private function findEligibleQuote(string $refId)
    {
        $personalQuote = PersonalQuote::whereIn('quote_type_id', $this->allowedPersonalQuoteTypeIds)
            ->where(function ($query) use ($refId) {
                $query->where('uuid', $refId)
                    ->orWhere('code', $refId);
            })->first();

        if ($personalQuote) {
            return $personalQuote;
        }

        return BusinessQuote::whereIn('quote_type_id', $this->allowedBusinessQuoteTypeIds)
            ->where(function ($query) use ($refId) {
                $query->where('uuid', $refId)
                    ->orWhere('code', $refId);
            })->first();
    }

    private function assignAdvisor($quote, int $advisorId): void
    {
        $quote->advisor_id = $advisorId;
        $quote->assignment_type = AssignmentTypeEnum::SYSTEM_REASSIGNED;
        $quote->save();
    }

    private function isManuallyAssigned($quote): bool
    {
        return in_array($quote->assignment_type, [
            AssignmentTypeEnum::MANUAL_ASSIGNED,
            AssignmentTypeEnum::MANUAL_REASSIGNED,
        ]);
    }
}
