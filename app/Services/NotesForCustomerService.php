<?php

namespace App\Services;

use App\Enums\EnvEnum;
use App\Models\NotesForCustomer;
use Illuminate\Support\Facades\Auth;
use Config;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\EmailActivityService;
use App\Services\EmailStatusService;

class NotesForCustomerService extends BaseService
{
    protected $emailActivityService;
    protected $emailStatusService;

    public function __construct(
        EmailActivityService $emailActivityService,
        EmailStatusService $emailStatusService
    ) {
        $this->emailActivityService = $emailActivityService;
        $this->emailStatusService = $emailStatusService;
    }

	public function getNotesForCustomer($quoteTypeId, $quoteId)
	{
		return NotesForCustomer::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
	}

	public function addCustomerNote($request)
	{
		$newNote = new NotesForCustomer();
		$newNote->quote_type_id = $request->quote_type_id;
		$newNote->quote_id = $request->quote_id;
		$newNote->description = nl2br($request->description);
		$newNote->created_by_id = Auth::user()->id;
		$newNote->save();

		return $newNote->id;
	}

	public function sendEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            $apiKey = Config::get('constants.SENDINBLUE_KEY');
            $url = Config::get('constants.SIB_URL');
            $appEnv = Config::get('constants.APP_ENV');

            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ];

            $body = json_encode([
                "to" => array([
                    "email" => $emailData['customerEmail'],
                    "name" => $emailData['customerName']
                ]),
                "templateId" => $emailTemplateId,
                "params" => [
                    "customerName" => $emailData['customerName'],
                    "customerEmail" => $emailData['customerEmail'],
                    "buttonUrl" => $emailData['buttonUrl'],
					"cdbId" => $emailData['quoteCdbId'],
					"notesForCustomer" => nl2br(htmlentities(str_replace("<br />", "", $emailData['notesForCustomer'])))
                ],
                "tags" => [
                    $tag
                ],
            ]);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

			$messageId = json_decode($clientRequest->getBody()->getContents())->messageId;
			$getResponse = json_decode(json_encode($clientRequest->getStatusCode()." ".$clientRequest->getBody()->getContents()),true);
            $getStatusCode = $clientRequest->getStatusCode();

            if($getStatusCode == 201 || $getStatusCode == 202) {
                $isEmailSent = 1;
            }
        }
        catch(Exception $ex) {
            $errorMessage = "SIB:  getCode/getMessage: ".$ex->getCode()."/".$ex->getMessage()." customerEmail: ".$emailData['customerEmail']." quoteCdbId: ".$emailData['quoteCdbId']." get_class: ".get_class();
            Log::channel('daily')->error($errorMessage);
            $getStatusCode = $ex->getCode();
            $getResponse = json_encode($ex->getCode()." ".$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($getResponse, $isEmailSent, $emailData['customerEmail']);

        if(isset($messageId)) {
            $this->emailStatusService->addEmailStatus($emailData, $messageId);
        }

        return $getStatusCode;
    }

}
