<?php

namespace App\Services\EmailServices;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\FailedIlaLeadsExport;
use App\Models\User;
use App\Services\BirdService;
use App\Services\Logger\LoggerService;
use Maatwebsite\Excel\Facades\Excel;

class FailedILAEmailService
{
    protected function getManagerEmailsByQuoteType($quoteType)
    {
        $roleMap = [
            QuoteTypes::CAR => RolesEnum::CarManager,
            QuoteTypes::BIKE => RolesEnum::BikeManager,
            QuoteTypes::HEALTH => RolesEnum::HealthManager,
            QuoteTypes::LIFE => RolesEnum::LifeManager,
            QuoteTypes::TRAVEL => RolesEnum::TravelManager,
            QuoteTypes::HOME => RolesEnum::HomeManager,
            QuoteTypes::PET => RolesEnum::PetManager,
            QuoteTypes::CYCLE => RolesEnum::CycleManager,
            QuoteTypes::SAVINGS => RolesEnum::SavingsManager,
            QuoteTypes::GROUP_MEDICAL => RolesEnum::GMManager,
            QuoteTypes::CORPLINE => RolesEnum::CorplineManager,
            QuoteTypes::YACHT => RolesEnum::YachtManager,
            QuoteTypes::JETSKI => RolesEnum::JetskiManager,
        ];
        $roleName = $roleMap[$quoteType] ?? null;

        return $this->getManagerEmails($roleName);
    }

    public function sendFailedIlaEmails(QuoteTypes$quoteType)
    {
        // Fetch leads created today (from midnight to now)
        $managerEmails = $this->getManagerEmailsByQuoteType($quoteType) ?? [];

        if (empty($managerEmails)) {
            LoggerService::warning(self::class." - sendFailedIlaEmails - No managers found for quote type: {$quoteType->value}");

            return;
        }

        $leadsCount = $this->getFailedILALeads($quoteType, justCount: true);

        if ($leadsCount === 0) {
            LoggerService::info(self::class." - sendFailedIlaEmails - No failed ILA leads found for quote type: {$quoteType->value}");

            return;
        }

        LoggerService::info(self::class.' - sendFailedIlaEmails - Sending failed ILA emails to managers: '.implode(', ', $managerEmails));
        $birdSendFailedIlaEmailsWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SEND_FAILED_ILA_EMAILS_WORKFLOW, useCache: true);
        if ($birdSendFailedIlaEmailsWorkflow) {
            LoggerService::info(self::class.' - sendFailedIlaEmails - Triggering web hook request for workflow: '.$birdSendFailedIlaEmailsWorkflow);
            app(BirdService::class)->triggerWebHookRequest($birdSendFailedIlaEmailsWorkflow, $this->buildFailedIlaEmailData($quoteType, $managerEmails));
            LoggerService::info(self::class.' - sendFailedIlaEmails - Web hook request triggered successfully');
        } else {
            LoggerService::warning(self::class.' - sendFailedIlaEmails - Workflow not found');
        }
    }

    public function getManagerEmails($roleName)
    {
        $managerEmails = User::role($roleName)->pluck('email')->toArray();

        return $managerEmails;
    }

    public function buildFailedIlaEmailData($quoteType, $managerEmails)
    {
        return (object) [
            'managerEmails' => $managerEmails,
            'quoteType' => $quoteType,
            'workflowType' => WorkflowTypeEnum::SEND_FAILED_ILA_EMAILS,
            'dateOfAttempt' => now()->format('Y-m-d'),
            'fileDownloadUrl' => route('export-failed-ila-leads', ['quoteType' => $quoteType]),
        ];
    }

    public function getFailedILALeads($quoteType, bool $justCount = false)
    {
        switch ($quoteType) {
            case QuoteTypes::CAR->value:
                $leads = $this->getCarFailedILALeads($justCount);
                break;
            case QuoteTypes::BIKE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::BIKE, $justCount);
                break;
            case QuoteTypes::HEALTH->value:
                $leads = $this->getHealthFailedILALeads($justCount);
                break;
            case QuoteTypes::LIFE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::LIFE, $justCount);
                break;
            case QuoteTypes::TRAVEL->value:
                $leads = $this->getTravelFailedILALeads($justCount);
                break;
            case QuoteTypes::HOME->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::HOME, $justCount);
                break;
            case QuoteTypes::PET->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::PET, $justCount);
                break;
            case QuoteTypes::CYCLE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::CYCLE, $justCount);
                break;
            case QuoteTypes::SAVINGS->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::SAVINGS, $justCount);
                break;
            case QuoteTypes::GROUP_MEDICAL->value:
                $leads = $this->getBusinessFailedILALeads(BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL, $justCount);
                break;
            case QuoteTypes::CORPLINE->value:
                $leads = $this->getBusinessFailedILALeads(justCount: $justCount);
                break;
            case QuoteTypes::YACHT->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::YACHT, $justCount);
                break;
            case QuoteTypes::JETSKI->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::JETSKI, $justCount);
                break;

            default:
                $leads = [];
                break;
        }

        return $leads;
    }

    private function getBaseQuery(QuoteTypes $quoteType)
    {
        return $quoteType->model()
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->endOfDay()])
            ->whereNull('advisor_id')
            ->whereNotNull('lead_allocation_failed_at')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL])
            ->with('quoteStatus');
    }

    public function getBusinessFailedILALeads($businessTypeOfInsuranceId = null, bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::BUSINESS)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id')
            ->when($businessTypeOfInsuranceId, function ($query) use ($businessTypeOfInsuranceId) {
                $query->where('business_type_of_insurance_id', $businessTypeOfInsuranceId);
            })
            ->when(! $businessTypeOfInsuranceId, function ($query) {
                $query->whereNotIn('business_type_of_insurance_id', [BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL]);
            })
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getHealthFailedILALeads(bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::HEALTH)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getTravelFailedILALeads(bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::TRAVEL)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getPersonalFailedILALeads(QuoteTypes $quoteType, bool $justCount = false)
    {
        return $this->getBaseQuery($quoteType)
            ->where('quote_type_id', $quoteType->id())
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function getCarFailedILALeads(bool $justCount = false)
    {
        return $this->getBaseQuery(QuoteTypes::CAR)
            ->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->when(
                $justCount,
                fn ($query) => $query->count(),
                fn ($query) => $query->get(),
            );
    }

    public function exportFailedIlaLeads($quoteType)
    {
        $leads = $this->getFailedILALeads($quoteType);
        $totalLeads = count($leads);
        LoggerService::info(self::class.' - exportFailedIlaLeads - Total leads: '.$totalLeads);

        $fileName = now()->format('Y-m-d_H-i-s').'-failed_ila_leads.xlsx';
        $export = new FailedIlaLeadsExport($leads);

        // Return both the streamed file response and total leads as array, following consistent API structure
        return [
            'file' => Excel::download($export, $fileName),
            'total_leads' => $totalLeads,
        ];
    }
}
