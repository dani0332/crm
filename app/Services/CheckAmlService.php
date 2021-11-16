<?php
namespace App\Services;

use App\Models\User;
use App\Models\QuoteType;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\BusinessQuote;
use App\Models\BikeQuote;
use App\Models\YachtQuote;
use App\Models\TravelQuote;
use App\Models\AML;
use App\Enums\quoteTypeCode;
use Illuminate\Support\Facades\Http;
use Config;
use Illuminate\Support\Facades\Mail;
use Auth;

class CheckAmlService
{
    public function checkAml($firstName,$lastName,$quoteRequestId,$quoteTypeId,$isEmailSendingEnabled)
    {
        $amlSearchEndPoint = Config::get('constants.AML_SEARCH_API_ENDPOINT');

        $emailL_sys = Config::get('constants.emailL_sys');
        $appUrl = env('APP_URL');
        $amlUrl = $appUrl.'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteRequestId;

        $dataArr = array("search" => $firstName." ".$lastName,"quoteRequestId" => $quoteRequestId,"quoteTypeId" => $quoteTypeId);
        $dataArrProc = json_encode($dataArr);

        $chAml = Http::contentType("application/json")->send('POST',$amlSearchEndPoint, ['body' => $dataArrProc]);
        $chAmlStatus = $chAml->status();
        $chAmlMessage = $chAml->json();

        $fullName = $firstName." ".$lastName;
        $resultsFound = $chAmlMessage["resultsFound"];

        // Match is found
        if($resultsFound > 0) {

            // Send Email alert to Compliance team only
            $quoteTypeName = QuoteType::where('id', '=', $quoteTypeId)->value('text'); // Get quote type text

            // Get CDB ID
            $quoteTypeCode = QuoteType::where('id', '=', $quoteTypeId)->value('code');
            if($quoteTypeCode == quoteTypeCode::Car) { $quoteCdbId = CarQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Home) { $quoteCdbId = HomeQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Health) { $quoteCdbId = HealthQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Life) { $quoteCdbId = LifeQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Business) { $quoteCdbId = BusinessQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Bike) { $quoteCdbId = BikeQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Yacht) { $quoteCdbId = YachtQuote::where('id', '=', $quoteRequestId)->value('code'); }
            if($quoteTypeCode == quoteTypeCode::Travel) { $quoteCdbId = TravelQuote::where('id', '=', $quoteRequestId)->value('code'); }

            if ($isEmailSendingEnabled == true) {
                $checkAmlService = new CheckAmlService();
                $checkAmlService->sendAMLMatchedEmailComplianceTeam($emailL_sys,$amlUrl,$resultsFound,$fullName,$quoteTypeName,$quoteCdbId);
            }
        }

        // API failed
        if($chAmlStatus != 201 && $chAmlStatus != 200) {
            $requestMessage = '';
            foreach ($chAmlMessage as $key1=>$value1) {
                $requestMessage .= $key1.': '.$value1;
                $requestMessage.= "<pre>";
            }

            $emailAmlData = '';
            foreach ($dataArr as $key => $value) {
                $emailAmlData .= $key . ': ' . $value;
                $emailAmlData .= "<pre>";
            }

            // Send Error Email alert to engineering team
            $checkAmlService = new CheckAmlService();
            $checkAmlService->sendAMLErrorEmailEngTeam($emailAmlData,$emailL_sys,$amlUrl,$chAmlStatus,$requestMessage);
        }
        return $chAmlStatus; // return http code
    }

    // Match found Email
    public function sendAMLMatchedEmailComplianceTeam($emailL_sys,$amlUrl,$resultsFound,$fullName,$quoteTypeName,$quoteCdbId)
    {
        $recipients = User::select('users.email as user_email')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','model_has_roles.role_id','roles.id')
        ->whereIn('roles.name', array("COMPLIANCE"))->get();

        $emailRecipients = array();
        foreach($recipients as $recipient) {
            $emailRecipients[] = $recipient->user_email;
        }

        if($emailL_sys == "PRODUCTION") {
            $emailSubject = "IMCRM | New AML Matches Found for CDB ID : ".$quoteCdbId;
        }
        else {
            $emailSubject = $emailL_sys." | IMCRM | New AML Matches Found for CDB ID : ".$quoteCdbId;
        }

        $checkAmlService = new CheckAmlService();
        $checkAmlService->amlComplianceMail('AmlComplianceMail', [
            'amlUrl' => $amlUrl,
            'resultsFound' => $resultsFound,
            'fullName' => $fullName,
            'quoteTypeName' => $quoteTypeName,
            'quoteCdbId' => $quoteCdbId,
        ], $emailSubject, $emailRecipients,$emailL_sys);
    }

    public function amlComplianceMail($templateName, $templateParams, $emailSubject, $emailRecipients, $emailL_sys)
	{
        if($emailL_sys == "PRODUCTION") {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = Config::get('constants.MAIL_FROM_NAME_AML');
        }
        else {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
            $fromName = Config::get('constants.MAIL_FROM_NAME');
        }

		Mail::send(['html' => $templateName], $templateParams,
            function ($message) use ($emailSubject, $emailRecipients, $fromName, $fromEmail) {
                $message->to($emailRecipients)->cc(Auth::user()->email)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
        });
	}

    public function sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName)
	{
        $complianceUsersEmails = User::select('users.email as user_email')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','model_has_roles.role_id','roles.id')
        ->whereIn('roles.name', array("COMPLIANCE"))->get();

        $complianceEmailRecipients = array();
        foreach($complianceUsersEmails as $complianceUsersEmail) {
            $complianceEmailRecipients[] = $complianceUsersEmail->user_email;
        }

        if($quotePaID != "") {
            // TO will be quotePaID
            $paUserEmailId = User::where('id', '=', $quotePaID)->value('email');
            $toRecipient = $paUserEmailId;

            // CC will be all users compliance
            $ccRecipients = $complianceEmailRecipients;
        }
        else {
            // TO will be currentUserID
            $currentUserEmailId = User::where('id', '=', Auth::user()->id)->value('email');
            $toRecipient = $currentUserEmailId;

            // CC will be all users compliance
            $ccRecipients = $complianceEmailRecipients;
        }

        $emailL_sys = Config::get('constants.emailL_sys');
        if($emailL_sys == "PRODUCTION") {
            $emailSubject = "IMCRM | New AML Matches Found for CDB ID : ".$quoteCdbId;
        }
        else {
            $emailSubject = $emailL_sys." | IMCRM | New AML Matches Found for CDB ID : ".$quoteCdbId;
        }

        $appUrl = env('APP_URL');
        $amlUrl = $appUrl.'/kyc/aml/'.$quoteTypeId.'/details/'.$quoteRequestId;

        $checkAmlService = new CheckAmlService();
        $checkAmlService->amlQuoteStatusUpdateMail('AmlQuoteStatusUpdateMail', [
            'amlUrl' => $amlUrl,
            'amlQuoteStatus' => $quoteStatusText,
            'clientFullName' => $clientFullName,
            'quoteTypeName' => $quoteTypeText,
            'quoteCdbId' => $quoteCdbId,
        ], $emailSubject, $toRecipient, $ccRecipients, $emailL_sys);
	}

    public function amlQuoteStatusUpdateMail($templateName, $templateParams, $emailSubject, $toRecipient, $ccRecipients, $emailL_sys)
	{
        if($emailL_sys == "PRODUCTION") {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS_AML');
            $fromName = Config::get('constants.MAIL_FROM_NAME_AML');
        }
        else {
            $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
            $fromName = Config::get('constants.MAIL_FROM_NAME');
        }

		Mail::send(['html' => $templateName], $templateParams,
            function ($message) use ($emailSubject, $toRecipient, $ccRecipients, $fromName, $fromEmail) {
                $message->to($toRecipient)->cc($ccRecipients)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
        });
	}

    // Error Email
    public function sendAMLErrorEmailEngTeam($emailAmlData,$emailL_sys,$amlUrl,$chAmlStatus,$requestMessage)
    {
        $errorEmailRecipients = Config::get('constants.ERROR_EMAIL_RECIPIENTS');
        $errorEmailRecipients = explode(',', $errorEmailRecipients);
        $subject = $emailL_sys." RYU SEARCH API ERROR | ".\Request::url()." | ".date('d-m-Y H:i:s');
        MailService::sendEmail('AmlErrorMail', [
            'amlUrl' => $amlUrl,
            'emailAmlData' => $emailAmlData,
            'chAmlStatus' => $chAmlStatus,
            'requestMessage' => $requestMessage,
        ], $subject, $errorEmailRecipients);
    }
}
