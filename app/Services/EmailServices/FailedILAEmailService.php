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
    public function sendFailedCarIlaEmails()
    {
        // Fetch leads created today (from midnight to now)
        $managerEmails = $this->getManagerEmails(RolesEnum::CarManager);
        LoggerService::info(self::class.' - sendFailedCarIlaEmails - Sending failed ILA emails to managers: '.implode(', ', $managerEmails));
        $birdSendFailedIlaEmailsWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SEND_FAILED_ILA_EMAILS_WORKFLOW, useCache: true);
        if ($birdSendFailedIlaEmailsWorkflow) {
            LoggerService::info(self::class.' - sendFailedCarIlaEmails - Triggering web hook request for workflow: '.$birdSendFailedIlaEmailsWorkflow);
            app(BirdService::class)->triggerWebHookRequest($birdSendFailedIlaEmailsWorkflow, $this->buildFailedIlaEmailData(QuoteTypes::CAR->value, $managerEmails));
            LoggerService::info(self::class.' - sendFailedCarIlaEmails - Web hook request triggered successfully');
        } else {
            LoggerService::warning(self::class.' - sendFailedCarIlaEmails - Workflow not found');
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
        if ($leads->isEmpty()) {
            LoggerService::warning(self::class . ' - exportFailedIlaLeads - No leads found for quote type: ' . $quoteType);
            return [
                'success' => false,
                'message' => 'No leads found for quote type: ' . $quoteType,
                'total_leads' => $totalLeads,
            ];
        }
        try {
            LoggerService::info(self::class . ' - exportFailedIlaLeads - Exporting failed ILA leads for quote type: ' . $quoteType);
         

            // Use Excel::raw to get the XLSX binary contents
            $excelContent = \Maatwebsite\Excel\Facades\Excel::raw(
                new FailedIlaLeadsExport($leads),
                \Maatwebsite\Excel\Excel::XLSX
            );
            // LoggerService::info(self::class . ' - exportFailedIlaLeads - Public URL generated for quote type: ' . $quoteType . ' | URL: ' . $publicUrl);
            return [
                'success' => true,
                'message' => 'Failed ILA leads exported successfully',
                'public_url' =>base64_encode($excelContent),
                'total_leads' => $totalLeads,
            ];
        } catch (\Exception $e) {
            LoggerService::warning(self::class . ' - exportFailedIlaLeads - Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to export failed ILA leads: ' . $e->getMessage(),
                'total_leads' => $totalLeads,
            ];
        }
    }
    protected function scheduleFileDeletion($filePath)
    {
        // Use a job to handle file deletion
        LoggerService::info(self::class . ' - scheduleFileDeletion - Scheduling file deletion for file: ' . $filePath);
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes(10));
    }
}
