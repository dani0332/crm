<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Models\BikeQuote;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use Auth;
use Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Traits\GenericQueriesAllLobs;
class CheckAmlService
{
    use GenericQueriesAllLobs;

    public function checkAml($firstName, $lastName, $quoteRequestId, $quoteTypeId, $isEmailSendingEnabled, $yob, $companyName)
    {
        $amlEndPoint = Config::get('constants.AML_SEARCH_API_ENDPOINT');
        $emailL_sys = Config::get('constants.emailL_sys');
        $appUrl = env('APP_URL');
        $amlUrl = $appUrl.'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteRequestId;
        $checkAMLResponseEntity = '';
        $isAMLResultFound = false;
        if ($companyName != null) {
            $checkAMLResponseEntity = $this->checkAMLRequestEntity($quoteRequestId, $quoteTypeId, $companyName, $amlEndPoint, $amlUrl);
        }
        $checkAMLResponseIndividual = $this->checkAMLRequestIndividual($firstName, $lastName, $quoteRequestId, $quoteTypeId, $yob, $amlEndPoint, $amlUrl);
        if($checkAMLResponseEntity) {
            $isAMLResultFound = $checkAMLResponseEntity && $checkAMLResponseEntity['resultsFound'] > 0 || $checkAMLResponseIndividual['resultsFound'] > 0 ? true : false;
        }

        // Match is found
        if ($isAMLResultFound) {

            // Send Email alert to Compliance team only
            $quoteTypeName = QuoteType::where('id', '=', $quoteTypeId)->value('text'); // Get quote type text

            // Get CDB ID
            $quoteTypeCode = QuoteType::where('id', '=', $quoteTypeId)->value('code');
            $quoteCdbId = $this->getQuoteCode($quoteTypeCode, $quoteRequestId);

            if ($isEmailSendingEnabled == true && $quoteCdbId) {
                $fullName = $firstName.' '.$lastName;
                if ($companyName != null) {
                    $this->sendAMLMatchedEmailComplianceTeam($emailL_sys, $amlUrl, $checkAMLResponseEntity, $companyName, $quoteTypeName, $quoteCdbId);
                }
                $this->sendAMLMatchedEmailComplianceTeam($emailL_sys, $amlUrl, $checkAMLResponseIndividual, $fullName, $quoteTypeName, $quoteCdbId);
            }
        }

        // API failed

        return ''; // return http code
    }

    public function checkAMLRequestIndividual($firstName, $lastName, $quoteRequestId, $quoteTypeId, $yob, $amlEndPoint, $amlUrl)
    {
        // creating the data for the request
        $requestDataForIndividual = [];
        $requestDataForIndividual['search'] = $firstName.' '.$lastName;
        $requestDataForIndividual['quoteRequestId'] = $quoteRequestId;
        $requestDataForIndividual['quoteTypeId'] = $quoteTypeId;
        $requestDataForIndividual['yob'] = $yob;
        //executing request
        $amlRequest = Http::contentType('application/json')->send('POST', $amlEndPoint.'/search', ['body' => json_encode($requestDataForIndividual)]);
        //capturing response
        $requestStatus = $amlRequest->status();
        $response = $amlRequest->json();
        // checking if the request wasn't successful
        if ($requestStatus != 201 && $requestStatus != 200) {
            $requestMessage = '';
            if (is_array($response) || is_object($response))
            {
                foreach ($response as $key1 => $value1) {
                    $requestMessage .= $key1.': '.$value1;
                    $requestMessage .= '<pre>';
                }
            }

            $emailAmlData = '';
            if (is_array($requestDataForIndividual) || is_object($requestDataForIndividual))
            {
                foreach ($requestDataForIndividual as $key => $value) {
                    $emailAmlData .= $key.': '.$value;
                    $emailAmlData .= '<pre>';
                }
            }

            // Send Error Email alert to engineering team
            $this->sendAMLErrorEmailEngTeam($emailAmlData, $amlUrl, $requestStatus, $requestMessage);
        }

        return $response;
    }

    public function checkAMLRequestEntity($quoteRequestId, $quoteTypeId, $companyName, $amlEndPoint, $amlUrl)
    {
        // creating the data for the request
        $requestDataForEntity = [];
        $requestDataForEntity['search'] = $companyName;
        $requestDataForEntity['quoteRequestId'] = $quoteRequestId;
        $requestDataForEntity['quoteTypeId'] = $quoteTypeId;
        //executing request
        $amlRequest = Http::contentType('application/json')->send('POST', $amlEndPoint.'/search-entity', ['body' => json_encode($requestDataForEntity)]);
        //capturing response
        $requestStatus = $amlRequest->status();
        $response = $amlRequest->json();
        // checking if the request wasn't successful
        if ($requestStatus != 201 && $requestStatus != 200) {
            $requestMessage = '';
            foreach ($response as $key1 => $value1) {
                $requestMessage .= $key1.': '.$value1;
                $requestMessage .= '<pre>';
            }

            $emailAmlData = '';
            foreach ($requestDataForEntity as $key => $value) {
                $emailAmlData .= $key.': '.$value;
                $emailAmlData .= '<pre>';
            }

            // Send Error Email alert to engineering team
            $this->sendAMLErrorEmailEngTeam($emailAmlData, $amlUrl, $requestStatus, $requestMessage);
        }

        return $response;
    }

    // Match found Email
    public function sendAMLMatchedEmailComplianceTeam($emailL_sys, $amlUrl, $AMLResponse, $fullName, $quoteTypeName, $quoteCdbId)
    {
        if ($AMLResponse['resultsFound'] == 0) {
            return;
        }
        $recipients = User::select('users.email as user_email')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'model_has_roles.role_id', 'roles.id')
            ->whereIn('roles.name', ['COMPLIANCE'])->get();

        $emailRecipients = [];
        foreach ($recipients as $recipient) {
            $emailRecipients[] = $recipient->user_email;
        }

        if ($emailL_sys == 'PRODUCTION') {
            $emailSubject = 'IMCRM | New AML Matches Found for CDB ID : '.$quoteCdbId;
        } else {
            $emailSubject = $emailL_sys.' | IMCRM | New AML Matches Found for CDB ID : '.$quoteCdbId;
        }

        $this->amlComplianceMail('AmlComplianceMail', [
            'amlUrl' => $amlUrl,
            'resultsFound' => $AMLResponse['resultsFound'],
            'fullName' => $fullName,
            'quoteTypeName' => $quoteTypeName,
            'quoteCdbId' => $quoteCdbId,
        ], $emailSubject, $emailRecipients, $emailL_sys);
    }

    public function amlComplianceMail($templateName, $templateParams, $emailSubject, $emailRecipients, $emailL_sys)
    {
        if ($emailL_sys == 'PRODUCTION') {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = Config::get('constants.MAIL_FROM_NAME_AML');
        } else {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
            $fromName = Config::get('constants.MAIL_FROM_NAME');
        }

        Mail::send(
            ['html' => $templateName],
            $templateParams,
            function ($message) use ($emailSubject, $emailRecipients, $fromName, $fromEmail) {
                $message->to($emailRecipients)->cc(Auth::user()->email)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
            }
        );
    }

    public function sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName)
    {
        $complianceUsersEmails = User::select('users.email as user_email')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'model_has_roles.role_id', 'roles.id')
            ->whereIn('roles.name', ['COMPLIANCE'])->get();

        $complianceEmailRecipients = [];
        foreach ($complianceUsersEmails as $complianceUsersEmail) {
            $complianceEmailRecipients[] = $complianceUsersEmail->user_email;
        }

        if ($quotePaID != '') {
            // TO will be quotePaID
            $paUserEmailId = User::where('id', '=', $quotePaID)->value('email');
            $toRecipient = $paUserEmailId;

            // CC will be all users compliance
            $ccRecipients = $complianceEmailRecipients;
        } else {
            // TO will be currentUserID
            $currentUserEmailId = User::where('id', '=', Auth::user()->id)->value('email');
            $toRecipient = $currentUserEmailId;

            // CC will be all users compliance
            $ccRecipients = $complianceEmailRecipients;
        }

        $emailL_sys = Config::get('constants.emailL_sys');
        if ($emailL_sys == 'PRODUCTION') {
            $emailSubject = 'IMCRM | New AML Matches Found for CDB ID : '.$quoteCdbId;
        } else {
            $emailSubject = $emailL_sys.' | IMCRM | New AML Matches Found for CDB ID : '.$quoteCdbId;
        }

        $appUrl = env('APP_URL');
        $amlUrl = $appUrl.'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteRequestId;

        $this->amlQuoteStatusUpdateMail('AmlQuoteStatusUpdateMail', [
            'amlUrl' => $amlUrl,
            'amlQuoteStatus' => $quoteStatusText,
            'clientFullName' => $clientFullName,
            'quoteTypeName' => $quoteTypeText,
            'quoteCdbId' => $quoteCdbId,
        ], $emailSubject, $toRecipient, $ccRecipients, $emailL_sys);
    }

    public function amlQuoteStatusUpdateMail($templateName, $templateParams, $emailSubject, $toRecipient, $ccRecipients, $emailL_sys)
    {
        if ($emailL_sys == 'PRODUCTION') {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = Config::get('constants.MAIL_FROM_NAME_AML');
        } else {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
            $fromName = Config::get('constants.MAIL_FROM_NAME');
        }

        Mail::send(
            ['html' => $templateName],
            $templateParams,
            function ($message) use ($emailSubject, $toRecipient, $ccRecipients, $fromName, $fromEmail) {
                $message->to($toRecipient)->cc($ccRecipients)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
            }
        );
    }

    // Error Email
    public function sendAMLErrorEmailEngTeam($emailAmlData, $amlUrl, $chAmlStatus, $requestMessage)
    {
        $email_sys = Config::get('constants.emailL_sys');
        $errorEmailRecipients = Config::get('constants.ERROR_EMAIL_RECIPIENTS');
        $errorEmailRecipients = explode(',', $errorEmailRecipients);
        $subject = $email_sys.' RYU SEARCH API ERROR | '.\Request::url().' | '.date('d-m-Y H:i:s');
        MailService::sendEmail('AmlErrorMail', [
            'amlUrl' => $amlUrl,
            'emailAmlData' => $emailAmlData,
            'chAmlStatus' => $chAmlStatus,
            'requestMessage' => $requestMessage,
        ], $subject, $errorEmailRecipients);
    }
}
