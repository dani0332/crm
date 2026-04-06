<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailEventsRequest;
use BaoPham\DynamoDb\Facades\DynamoDb;
use Carbon\Carbon;

class FollowupController extends Controller
{
    public function getEmailEvents(EmailEventsRequest $request)
    {
        $response = DynamoDb::table(config('constants.DYNAMO_EMAIL_CONTENT_TABLE'))
            ->setKeyConditionExpression('#recipientEmail = :recipientEmail')
            ->setFilterExpression('#messageID = :messageID')
            ->setExpressionAttributeName('#recipientEmail', 'recipientEmail')
            ->setExpressionAttributeValue(':recipientEmail', DynamoDb::marshalValue($request->customer_email))
            ->setExpressionAttributeName('#messageID', 'messageID')
            ->setExpressionAttributeValue(':messageID', DynamoDb::marshalValue($request->message_id))
            ->prepare()
            ->query();

        $events = [];

        if ($response->get('Items')) {
            foreach ($response->get('Items') as $item) {
                $events[] = [
                    'type' => $item['messageType']['S'],
                    'sub_type' => $item['messageSubType']['S'],
                    'event_date' => Carbon::createFromTimestamp(substr($item['eventOccurredAt']['N'], 0, 10))->format('d-M-Y H:i:s')];
            }
        }

        return response()->json($events);
    }
}
