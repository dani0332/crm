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

    protected function getManagerEmailsByQuoteType($quoteType){
        $roleMap = [
            QuoteTypes::CAR          => RolesEnum::CarManager,
            QuoteTypes::BIKE         => RolesEnum::BikeManager,
            QuoteTypes::HEALTH       => RolesEnum::HealthManager,
            QuoteTypes::LIFE         => RolesEnum::LifeManager,
            QuoteTypes::TRAVEL       => RolesEnum::TravelManager,
            QuoteTypes::HOME         => RolesEnum::HomeManager,
            QuoteTypes::PET          => RolesEnum::PetManager,
            QuoteTypes::CYCLE        => RolesEnum::CycleManager,
            QuoteTypes::SAVINGS      => RolesEnum::SavingsManager,
            QuoteTypes::GROUP_MEDICAL=> RolesEnum::GMManager,
            QuoteTypes::CORPLINE     => RolesEnum::CorplineManager,
            QuoteTypes::YACHT        => RolesEnum::YachtManager,
            QuoteTypes::JETSKI       => RolesEnum::JetskiManager,
        ];
        $roleName = $roleMap[$quoteType] ?? null;
        return $this->getManagerEmails($roleName);
    }
    public function sendFailedIlaEmails($quoteType)
    {
        // Fetch leads created today (from midnight to now)
        $managerEmails = $this->getManagerEmailsByQuoteType($quoteType) ?? [];
        
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
            ->whereNotNull('lead_allocation_failed_at')
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
            ->whereNotNull('lead_allocation_failed_at')
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
            ->whereNotNull('lead_allocation_failed_at')
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
            ->whereNotNull('lead_allocation_failed_at')
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
            ->whereNotNull('lead_allocation_failed_at')
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
