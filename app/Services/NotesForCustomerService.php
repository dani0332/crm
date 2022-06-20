<?php

namespace App\Services;

use App\Models\EmailActivity;
use App\Models\NotesForCustomer;
use Illuminate\Support\Facades\Auth;
use Config;
use Exception;
use Illuminate\Support\Facades\Log;

class NotesForCustomerService extends BaseService
{
	public static function getNotesForCustomer($quoteTypeId, $quoteId)
	{
		return NotesForCustomer::where(['quote_type_id' => $quoteTypeId, 'quote_id' => $quoteId])
        ->orderBy('updated_at', 'desc')
        ->get();
	}

	public static function AddNoteForCustomer($request)
	{
		$newNote = new NotesForCustomer();
		$newNote->quote_type_id = $request->quote_type_id;
		$newNote->quote_id = $request->quote_id;
		$newNote->description = $request->description;
		$newNote->created_by_id = Auth::user()->id;
		$newNote->save();

		return $newNote->id;
	}

	public function sendEmail($emailTemplateId, $emailData, $tag)
    {
		$notesForCustomer = $this->getNotesForCustomer($emailData['quoteTypeId'], $emailData['quoteId']);

        try {

            $apiKey = Config::get('constants.SENDINBLUE_KEY');
            $url = Config::get('constants.SIB_URL');
            $appEnv = Config::get('constants.APP_ENV');

            if($appEnv == 'production') {
                $tag = $tag;
            }
            else {
                $tag = $appEnv.'-'.$tag;
            }

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
					"notesForCustomer" => $notesForCustomer
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

			$getMsgId = json_decode($clientRequest->getBody()->getContents())->messageId;
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

        EmailActivityService::addEmailActivity($getResponse, $isEmailSent, $emailData['customerEmail']);
		EmailStatusService::addEmailStatus($emailData['quoteTypeId'],$emailData['quoteId'],$emailData['customerEmail'],$getMsgId);

        return $getStatusCode;
    }

}
