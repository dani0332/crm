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
use App\Exports\FailedIlaLeadsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Logger\LoggerService;
use App\Models\BikeQuote;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
use App\Models\BusinessQuote;
use App\Enums\BusinessTypeOfInsuranceIdEnum;

class FailedILAEmailService
{
    public function sendFailedIlaEmails($quoteType)
    {
        // Fetch leads created today (from midnight to now)
        $managerEmails = [];
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
            case QuoteTypes::YACHT:
                $managerEmails = $this->getManagerEmails(RolesEnum::YachtManager);
                break;
            case QuoteTypes::JETSKI:
                $managerEmails = $this->getManagerEmails(RolesEnum::JetskiManager);
                break;

            default:
                $managerEmails = [];
                break;
        }
        if (empty($managerEmails)) {
            LoggerService::warning(self::class . ' - sendFailedIlaEmails - No managers found for quote type: ' . $quoteType);
            return;
        }
        LoggerService::info(self::class . ' - sendFailedIlaEmails - Sending failed ILA emails to managers: ' . implode(', ', $managerEmails));
        $birdSendFailedIlaEmailsWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SEND_FAILED_ILA_EMAILS_WORKFLOW, useCache: true);
        if ($birdSendFailedIlaEmailsWorkflow) {
            LoggerService::info(self::class . ' - sendFailedIlaEmails - Triggering web hook request for workflow: ' . $birdSendFailedIlaEmailsWorkflow);
            app(BirdService::class)->triggerWebHookRequest($birdSendFailedIlaEmailsWorkflow, $this->buildFailedIlaEmailData($quoteType, $managerEmails));
            LoggerService::info(self::class . ' - sendFailedIlaEmails - Web hook request triggered successfully');
        } else {
            LoggerService::warning(self::class . ' - sendFailedIlaEmails - Workflow not found');
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
            case QuoteTypes::BIKE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::BIKE->id());
                break;
            case QuoteTypes::HEALTH->value:
                $leads = $this->getHealthFailedILALeads();
                break;

            case QuoteTypes::LIFE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::LIFE->id());
                break;
            case QuoteTypes::TRAVEL->value:
                $leads = $this->getTravelFailedILALeads();
                break;
            case QuoteTypes::HOME->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::HOME->id());
                break;
            case QuoteTypes::PET->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::PET->id());
                break;
            case QuoteTypes::CYCLE->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::CYCLE->id());
                break;
            case QuoteTypes::SAVINGS->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::SAVINGS->id());
                break;
            case QuoteTypes::GROUP_MEDICAL->value:
                $leads = $this->getBusinessFailedILALeads(BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
                break;
            case QuoteTypes::CORPLINE->value:
                $leads = $this->getBusinessFailedILALeads();
                break;
            case QuoteTypes::YACHT->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::YACHT->id());
                break;
            case QuoteTypes::JETSKI->value:
                $leads = $this->getPersonalFailedILALeads(QuoteTypes::JETSKI->id());
                break;
                break;
            default:
                $leads = [];
                break;
        }
        return $leads;
    }
   
    public function getBusinessFailedILALeads($businessTypeOfInsuranceId = null)
    {
        $leads = BusinessQuote::select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id')
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->endOfDay()])
            ->when($businessTypeOfInsuranceId, function ($query) use ($businessTypeOfInsuranceId) {
                $query->where('business_type_of_insurance_id', $businessTypeOfInsuranceId);
            })
            ->when(!$businessTypeOfInsuranceId, function ($query) {
                $query->whereNotIn('business_type_of_insurance_id', [BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL]);
            })

            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY,LeadSourceEnum::REVIVAL])           
            ->with('quoteStatus')
            ->get();
        return $leads;
    }
    public function getHealthFailedILALeads()
    {
        $leads = HealthQuote::select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->endOfDay()])
            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY,LeadSourceEnum::REVIVAL])           
            ->where('lead_allocation_failed_at', '!=', null)
            ->with('quoteStatus')
            ->get();
        return $leads;
    }
    public function getTravelFailedILALeads()
    {
        $leads = TravelQuote::select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->endOfDay()])
            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY,LeadSourceEnum::REVIVAL])           
            ->where('lead_allocation_failed_at', '!=', null)
            ->with('quoteStatus')
            ->get();
        return $leads;
    }
    public function getPersonalFailedILALeads($quoteTypeId = null)
    {
        $leads = PersonalQuote::where('quote_type_id', $quoteTypeId)->select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->endOfDay()])
            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY,LeadSourceEnum::REVIVAL])           
            ->where('lead_allocation_failed_at', '!=', null)
            ->with('quoteStatus')
            ->get();
        return $leads;
    }

    public function getCarFailedILALeads()
    {

        $leads = CarQuote::select('id', 'code', 'uuid', 'first_name', 'last_name', 'created_at', 'quote_status_id', 'paid_at', 'lead_allocation_failed_at')
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->endOfDay()])
            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY,LeadSourceEnum::REVIVAL])           
            ->where('lead_allocation_failed_at', '!=', null)
            ->with('quoteStatus')
            ->get();
        return $leads;
    }
    public function exportFailedIlaLeads($quoteType)
    {

        $leads = $this->getFailedILALeads($quoteType);
        $totalLeads = count($leads);
        LoggerService::info(self::class . ' - exportFailedIlaLeads - Total leads: ' . $totalLeads);

        $fileName = now()->format('Y-m-d_H-i-s') . '-failed_ila_leads.xlsx';
        $export = new FailedIlaLeadsExport($leads);
        // Return both the streamed file response and total leads as array, following consistent API structure
        return [
            'file' => Excel::download($export, $fileName),
            'total_leads' => $totalLeads,
        ];
    }
}
