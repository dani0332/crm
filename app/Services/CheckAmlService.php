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

class CheckAmlService
{
    public function checkAml($firstName,$lastName,$quoteRequestId,$quoteTypeId)
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

        $getTotalResults = AML::where('quote_type_id', $quoteTypeId)
        ->where('quote_request_id', $quoteRequestId)
        ->sum('results_found');

        if(($resultsFound > 0 || $getTotalResults > 0) && stripos($fullName, "test") === false) { // Match is found

            // Send Email alert to Compliance team
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

            CheckAmlService::sendAMLMatchedEmailComplianceTeam($emailL_sys,$amlUrl,$resultsFound,$fullName,$quoteTypeName,$quoteCdbId);
        }
        if($chAmlStatus != 201 && $chAmlStatus != 200) { // API failed
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
            CheckAmlService::sendAMLErrorEmailEngTeam($emailAmlData,$emailL_sys,$amlUrl,$chAmlStatus,$requestMessage);
        }
        return $chAmlStatus; // return http code
    }
    // Match found Email
    public static function sendAMLMatchedEmailComplianceTeam($emailL_sys,$amlUrl,$resultsFound,$fullName,$quoteTypeName,$quoteCdbId)
    {
        $recipients = User::select('users.email as user_email')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','model_has_roles.role_id','roles.id')
        ->whereIn('roles.name', array("COMPLIANCE"))->get();

        $emailRecipients = array();
        foreach($recipients as $recipient) {
            $emailRecipients[] = $recipient->user_email;
        }

        $emailSubject = $emailL_sys." IMCRM | New AML Match Found";
        MailService::sendEmail('AmlComplianceMail', [
            'amlUrl' => $amlUrl,
            'resultsFound' => $resultsFound,
            'fullName' => $fullName,
            'quoteTypeName' => $quoteTypeName,
            'quoteCdbId' => $quoteCdbId,
        ], $emailSubject, $emailRecipients);
    }
    // Error Email
    public static function sendAMLErrorEmailEngTeam($emailAmlData,$emailL_sys,$amlUrl,$chAmlStatus,$requestMessage)
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
