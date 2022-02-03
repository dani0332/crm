<?php

namespace App\Services;

use App\Models\CarQuote;
use Config;

class CapiRequestService
{
    public static function sendCAPIRequest($endpoint, $data)
    {
        $apiEndPoint = Config::get('constants.CENTRAL_API_ENDPOINT') . $endpoint;
        $apiToken = Config::get('constants.CENTRAL_API_TOKEN');
        $apiTimeout = Config::get('constants.CENTRAL_API_TIMEOUT');

        $client = new \GuzzleHttp\Client();
        $capiRequest = $client->post(
            $apiEndPoint,

            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $apiToken],
                'body' => json_encode($data),
                'timeout' => $apiTimeout,
            ]
        );

        $getStatusCode = $capiRequest->getStatusCode();

        if ($getStatusCode == 200) {
            $getContents = $capiRequest->getBody();
            $getdecodeContents = json_decode($getContents);

            if (isset($data['carTypeInsuranceId']) && $data['carTypeInsuranceId'] != '') {
                $carQuoteId = CarQuote::where('uuid', '=', $getdecodeContents->quoteUID)->value('id');
                $carQuoteUpdate = CarQuote::find($carQuoteId);
                if ($carQuoteUpdate) {
                    $carQuoteUpdate->cylinder = $data['cylinder'];
                    $carQuoteUpdate->seat_capacity = $data['seatCapacity'];
                    $carQuoteUpdate->vehicle_type_id = $data['vehicleTypeId'];
                    $carQuoteUpdate->save();
                }
            }

            return $getdecodeContents;
        } else {
            return "API failed";
        }
    }
}
