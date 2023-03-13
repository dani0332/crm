<?php

namespace App\Services;

use App\Enums\UserNameEnum;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\User;

class CapiRequestService
{
    public static function sendCAPIRequest($endpoint, $data)
    {
        $apiEndPoint = config('constants.CENTRAL_API_ENDPOINT').$endpoint;
        $apiToken = config('constants.CENTRAL_API_TOKEN');
        $apiTimeout = config('constants.CENTRAL_API_TIMEOUT');

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
                $carQuote = CarQuote::where('uuid', $getdecodeContents->quoteUID)->first();
                if ($carQuote) {
                    $carQuote->cylinder = $data['cylinder'];
                    $carQuote->seat_capacity = $data['seatCapacity'];
                    $carQuote->vehicle_type_id = $data['vehicleTypeId'];
                    $carQuote->is_quote_locked = true;
                    $carQuote->car_model_detail_id = $data['trim'];
                    $carQuote->car_value_tier = $data['carValueTier'];
                    $carQuote->save();

                    $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $carQuote->id)->first();
                    if ($carQuoteDetail != null) {
                        $carQuoteDetail->advisor_assigned_date = now();
                        $carQuoteDetail->advisor_assigned_by_id = auth()->id();
                        $carQuoteDetail->save();
                        info('---- updateCarLeadDetailRecord - update done for advisor data and by id');
                    } else {
                        info('---- updateCarLeadDetailRecord - record not found creating new entry');
                        CarQuoteRequestDetail::create([
                            'car_quote_request_id' => $$carQuote->id,
                            'advisor_assigned_date' => now(),
                            'advisor_assigned_by_id' =>  auth()->user()->id ?? User::where('name', UserNameEnum::System)->first(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            return $getdecodeContents;
        } else {
            return 'API failed';
        }
    }

    public static function getUUID($type)
    {
        $response = self::sendCAPIRequest('/api/v1-get-uuid', ['quoteTypeId' => $type]);
        if ($response) {
            return $response;
        } else {
            return false;
        }
    }
}
