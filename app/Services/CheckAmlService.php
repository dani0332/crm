<?php
namespace App\Services;

use App\Models\AML;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Enums\quoteTypeCode;
use Config;
use Illuminate\Support\Facades\Mail;
use App\Models\QuoteType;
use App\Models\QuoteStatus;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\BusinessQuote;
use App\Models\BikeQuote;
use App\Models\YachtQuote;
use App\Models\TravelQuote;

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

        $emailAmlData = '';
        foreach ($dataArr as $key => $value) {
            $emailAmlData .= $key . ': ' . $value;
            $emailAmlData .= "<pre>";
        }

        if($chAmlStatus == 201) { // Match is found

            // Update Quote Status 
            $quoteTypeCode = QuoteType::where('id', '=', $quoteTypeId)->value('code');
            $quoteStatusId = QuoteStatus::where('code', '=', 'approvalRequired')->value('id');
            if($quoteTypeCode && $quoteTypeCode != "") {

                if($quoteTypeCode == quoteTypeCode::Car) { $updateQuote = CarQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Home) { $updateQuote = HomeQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Health) { $updateQuote = HealthQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Life) { $updateQuote = LifeQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Business) { $updateQuote = BusinessQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Bike) { $updateQuote = BikeQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Yacht) { $updateQuote = YachtQuote::find($quoteRequestId); }
                if($quoteTypeCode == quoteTypeCode::Travel) { $updateQuote = TravelQuote::find($quoteRequestId); }

                $quoteStatusUpdate = $updateQuote;
                $quoteStatusUpdate->quote_status_id = $quoteStatusId;
                $quoteStatusUpdate->save();
            }

            // Send Email alert to Compliance team
            CheckAmlService::sendAMLMatchedEmailComplianceTeam($emailAmlData,$emailL_sys,$amlUrl);
        }
        if($chAmlStatus != 201 && $chAmlStatus != 200) { // API failed
            $requestMessage = '';
            foreach ($chAmlMessage as $key1=>$value1) {
                $requestMessage .= $key1.': '.$value1;
                $requestMessage.= "<pre>";
            }

            // Send Error Email alert to engineering team
            CheckAmlService::sendAMLErrorEmailEngTeam($emailAmlData,$emailL_sys,$amlUrl,$chAmlStatus,$requestMessage);
        }
        return $chAmlStatus; // return http code
    }
    // Match found Email
    public static function sendAMLMatchedEmailComplianceTeam($emailAmlData,$emailL_sys,$amlUrl)
    {
        $amlMatchedEmailRecipients = Config::get('constants.AML_MATCHED_EMAIL_RECIPIENTS');
        $amlMatchedEmailRecipients = explode(',', $amlMatchedEmailRecipients);
        $subject = $emailL_sys." AML ALERT - Quote approval required";
        MailService::sendEmail('AmlComplianceMail', [
            'amlUrl' => $amlUrl,
            'emailAmlData' => $emailAmlData,
        ], $subject, $amlMatchedEmailRecipients);
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