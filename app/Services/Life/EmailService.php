<?php

namespace App\Services\Life;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Services\BirdService;
use App\Models\ApplicationStorage;
use App\Enums\ApplicationStorageEnums;
use App\Services\WorkflowTypeEnum;
use App\Models\User;
use App\Models\LifeQuote;
use App\Enums\QuoteTypeId;


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

        // map data for bird service
        $emailData = $this->mapOCAEmailData($lead);

        // get bird flow url for Life from ApplicationStorage
        $flowUrl = $this->getApplicationStorage();
        if(!$flowUrl){
            LoggerService::info($logPrefix . ' - Flow URL not found');
            return false;
        }

        

        
        LoggerService::info($logPrefix . ' - Initiating process');
    }

    private function getApplicationStorage(){
        return ApplicationStorage::where('key_name', ApplicationStorageEnums::LIFE_OCA_EMAIL_FLOW)->value('value');
    }
    private function mapOCAEmailData($lead)
    {
        // $data = [
        //     // Lead-related data
        //     'quoteUID' => $lead->uuid,
        //     'uuid' => $lead->uuid,
        //     'customerEmail' => $lead->email,
        //     'customerFullName' => trim("{$lead->first_name} {$lead->last_name}"),
        //     'customerName' => trim("{$lead->first_name} {$lead->last_name}"),
        //     'refID' => $lead->code,
        //     'customerMobile' => $lead->mobile_no ?? '',
        //     'whatsappConsent' => getWhatsappConsent(QuoteTypes::HOME, $lead->uuid),
        //     'flowExecutedAt' => $lead->automated_flow_executed_at ?? null,

        //     // Advisor-related data
        //     'advisorId' => $advisor?->id,
        //     'advisorName' => $advisor?->name ?? '',
        //     'advisorEmail' => $advisor?->email ?? '',
        //     'advisorDetails' => $advisor ?? null,
        //     'landLine' => $advisor?->landline_no ?? '',
        //     'mobilePhone' => $advisor?->mobile_no ?? '',
        //     'whatsAppNumber' => $advisor?->mobile_no ? formatMobileNo($advisor->mobile_no) : '',
        //     'mobileNoWithoutSpaces' => $advisor?->mobile_no ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '',

        //     // Workflow-related data
        //     'workflowType' => $workflowType,
        // ];

        // $tempUrlPDF = $this->attachHomeOCBPDFToEmail($lead->uuid);

        // if (! empty($tempUrlPDF)) {
        //     $data['tempUrlPDF'] = $tempUrlPDF;
        // }

        // return (object) $data;
    }

    private function getQuote(string $quoteUID){
        return PersonalQuote::where([
            'uuid' => $quoteUID,
            'quote_type_id' => QuoteTypeId::Life
        ])->first();
    }
}
