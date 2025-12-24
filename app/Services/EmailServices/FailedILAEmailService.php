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
      $birdSendFailedIlaEmailsWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_SEND_FAILED_ILA_EMAILS_WORKFLOW, useCache: true);
      if ($birdSendFailedIlaEmailsWorkflow) {
        app(BirdService::class)->triggerWebHookRequest($birdSendFailedIlaEmailsWorkflow, $this->buildFailedIlaEmailData(QuoteTypes::CAR, $managerEmails));
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
      ];
    }

    public function getFailedILALeads($startOfDay, $endOfDay, $quoteType)
    {
      switch ($quoteType) {
        case QuoteTypes::CAR->value:
          $leads = $this->getCarFailedILALeads($startOfDay, $endOfDay);
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
    public function getCarFailedILALeads($startOfDay, $endOfDay)
    {
      $leads = CarQuote::whereBetween('created_at', [$startOfDay, $endOfDay])
        ->select('id', 'uuid','first_name','last_name','created_at','quote_status_id','payment_status_id','lead_allocation_failed_at')
        ->whereNull('advisor_id')
        ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
        ->where('source', '!=', LeadSourceEnum::IMCRM)
        ->where('lead_allocation_failed_at', '!=', null)
        ->get();
      return $leads;
    }
    public function exportFailedIlaLeads($quoteType)
    {

      $leads = $this->getFailedILALeads(now()->startOfDay(), now(), $quoteType);
      if ($leads->isEmpty()) {
        return null;
      }
      try {
      $file = Excel::download(new FailedIlaLeadsExport($leads), now().'-failed_ila_leads.xlsx');
    // Generate a unique temporary file path
            $tempFilePath = 'temp/'.uniqid().'.xlsx';
            Storage::disk('azureIM')->put($tempFilePath, $file->getContent());

            // Generate a public URL
            $publicUrl = Storage::disk('azureIM')->temporaryUrl(
                $tempFilePath,
                now()->addMinutes(10)
            );
            // Schedule deletion after 5 minutes
            $this->scheduleFileDeletion($tempFilePath);

            LoggerService::info(self::class.' - exportFailedIlaLeads - Public URL generated for quote type: '.$quoteType.' | URL: '.$publicUrl);
            return $publicUrl;
      } catch (\Exception $e) {
        LoggerService::warning(self::class.' - exportFailedIlaLeads - Error: '.$e->getMessage());
        return null;
      }
       
    }
    protected function scheduleFileDeletion($filePath)
    {
        // Use a job to handle file deletion
        DeleteTempOCBPDFFileJob::dispatch($filePath)->delay(now()->addMinutes(10));
    }
}