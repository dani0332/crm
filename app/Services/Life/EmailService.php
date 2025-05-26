<?php

namespace App\Services\Life;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Services\BirdService;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Models\User;
use App\Models\LifeQuote;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;

class EmailService
{
    /**
     * Create a new class instance.
     */
    function sendOCAEmail(string $quoteUID){
        
        $logPrefix = 'Life - Send OCA Email';

        $lead = $this->getQuote($quoteUID);

        if (! $lead) {
            LoggerService::info($logPrefix . ' - Lead not found');
            return false;
        }

        // check plans, skip the email if the plan is zero
        $plans = $this->getPlans($lead);
        if(count($plans) == 0){
            LoggerService::info($logPrefix . ' - Skipping OCA email on zero plans');
            return false;
        }
        
        // map data for bird service
        $emailData = $this->mapOCAEmailData($lead, $plans);
        
        // get bird flow url for Life from ApplicationStorage
        $flowUrl = $this->getApplicationStorage();
        if(!$flowUrl){
            LoggerService::info($logPrefix . ' - Flow URL not found');
            return false;
        }

        dd($flowUrl);

        
        LoggerService::info($logPrefix . ' - Initiating process');
    }

    private function getPlans(PersonalQuote $quote){
        $quotePlans = app(LifeQuoteService::class)->getQuotePlans($quote->uuid);
        $lifePlans = $quotePlans->quotes->plans;
        return $lifePlans;  
    }

    private function attachComparisionPdf(PersonalQuote $quote, $plans){
        $lifePlans = $plans; 
        $planIds = collect($lifePlans)->take(5)->pluck('_id')->toArray();
        $pdf = app(LifeQuoteService::class)->exportComparisionPdf($quote, $planIds, $lifePlans);
        return $pdf;
    }

    private function getApplicationStorage(){
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::LIFE_OCA_EMAIL_FLOW)->value('value');
    }
    private function mapOCAEmailData($lead, $plans)
    {
        $firstName = $lead->first_name;
        $lastName = $lead->last_name;
        $customerFullName = trim("{$firstName} {$lastName}");
        $advisor = $lead->advisor;
        $workflowType = WorkflowTypeEnum::LIFE_OCA_EMAIL;
        
        $data = [
            // Lead-related data
            'quoteUID' => $lead->uuid,
            'uuid' => $lead->uuid,
            'customerEmail' => $lead->email,
            'customerFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'refID' => $lead->code,
            'customerMobile' => $lead->mobile_no ?? '',
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::LIFE, $lead->uuid),
            'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

            // Advisor-related data
            'advisorId' => $advisor?->id,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorDetails' => $advisor ?? null,
            'landLine' => $advisor?->landline_no ?? '',
            'mobilePhone' => $advisor?->mobile_no ?? '',
            'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',

            // Workflow-related data
            'workflowType' => $workflowType,
        ];

        $tempUrlPDF = $this->attachComparisionPdf($lead, $plans);

        if (! empty($tempUrlPDF)) {
            $data['tempUrlPDF'] = $tempUrlPDF;
        }

        return (object) $data;
    }

    private function getQuote(string $quoteUID){
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Life
        ])->first();
    }
}
