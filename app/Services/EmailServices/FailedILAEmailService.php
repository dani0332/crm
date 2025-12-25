<?php

namespace App\Services\EmailServices;

use App\Models\CarQuote;
use App\Enums\QuoteStatusEnum;
use App\Enums\LeadSourceEnum;
use App\Models\User;
use App\Enums\RolesEnum;
use App\Enums\ApplicationStorageEnums;
use App\Enums\WorkflowTypeEnum;
use App\Services\BirdService;
use App\Enums\QuoteTypes;
use Illuminate\Support\Facades\Storage;
use App\Exports\FailedIlaLeadsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Logger\LoggerService;
use App\Jobs\DeleteTempOCBPDFFileJob;

class FailedILAEmailService
{
    public function sendFailedIlaEmails($quoteType)
    {
        // Fetch leads created today (from midnight to now)
        $managerEmails =[];
        switch ($quoteType) {
            case QuoteTypes::CAR:
                $managerEmails = $this->getManagerEmails(RolesEnum::CarManager);
                break;
            case QuoteTypes::BIKE:
                $managerEmails = $this->getManagerEmails(RolesEnum::BikeManager);
                break;
            case QuoteTypes::HEALTH:
                $managerEmails = $this->getManagerEmails(RolesEnum::HealthManager);
                break;
            case QuoteTypes::LIFE:
                $managerEmails = $this->getManagerEmails(RolesEnum::LifeManager);
                break;
            case QuoteTypes::TRAVEL:
                $managerEmails = $this->getManagerEmails(RolesEnum::TravelManager);
                break;
            case QuoteTypes::HOME:
                $managerEmails = $this->getManagerEmails(RolesEnum::HomeManager);
                break;
            case QuoteTypes::PET:
                $managerEmails = $this->getManagerEmails(RolesEnum::PetManager);
                break;
            case QuoteTypes::CYCLE:
                $managerEmails = $this->getManagerEmails(RolesEnum::CycleManager);
                break;
            case QuoteTypes::SAVINGS:
                $managerEmails = $this->getManagerEmails(RolesEnum::SavingsManager);
                break;
            case QuoteTypes::GROUP_MEDICAL:
                $managerEmails = $this->getManagerEmails(RolesEnum::GMManager);
                break;
            case QuoteTypes::CORPLINE:
                $managerEmails = $this->getManagerEmails(RolesEnum::CorplineManager);
                break;
         
            default:
                $managerEmails = [];
                break;
            }
        if (empty($managerEmails)) {
            LoggerService::warning(self::class.' - sendFailedIlaEmails - No managers found for quote type: '.$quoteType);
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

    public function getFailedILALeads($quoteType)
    {
        switch ($quoteType) {
            case QuoteTypes::CAR->value:
                $leads = $this->getCarFailedILALeads();
                break;
                // case QuoteTypes::BIKE:
                //   $leads = $this->getBikeFailedILALeads($startOfDay, $endOfDay);
                //   break;
                // case QuoteTypes::HEALTH:
                //   $leads = $this->getHealthFailedILALeads($startOfDay, $endOfDay);
                //   break;
                // case QuoteTypes::LIFE:
                //   $leads = $this->getLifeFailedILALeads($startOfDay, $endOfDay);
                break;
            default:
                $leads = [];
                break;
        }
        return $leads;
    }
    public function getCarFailedILALeads()
    {
     
        $leads = CarQuote::select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->where('source', '!=', LeadSourceEnum::IMCRM)
            ->where('lead_allocation_failed_at', '!=', null)
            ->with('quoteStatus')
            ->get();
        return $leads;
    }
    public function exportFailedIlaLeads($quoteType)
    {

        $leads = $this->getFailedILALeads( $quoteType);
        $totalLeads = count($leads);

        $fileName = now()->format('Y-m-d_H-i-s') . '-failed_ila_leads.xlsx';
        $export = new FailedIlaLeadsExport($leads);
        // Return both the streamed file response and total leads as array, following consistent API structure
        return [
            'file' => Excel::download($export, $fileName),
            'total_leads' => $totalLeads,
        ];
    }
  
}
