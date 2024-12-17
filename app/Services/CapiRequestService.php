<?php

namespace App\Services;

use App\Enums\UserNameEnum;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\QuoteBatches;
use App\Models\User;

class CapiRequestService
{
    public static function sendCAPIRequest($endpoint, $data, $quoteModel = null)
    {
        $apiEndPoint = config('constants.CENTRAL_API_ENDPOINT').$endpoint;
        $apiToken = config('constants.CENTRAL_API_TOKEN');
        $apiTimeout = config('constants.CENTRAL_API_TIMEOUT');

        $client = new \GuzzleHttp\Client;
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
            switch ($quoteModel) {
                case CarQuote::class:
                    self::handleCarResponse($getdecodeContents);
                    break;
                case HealthQuote::class:
                    self::handleHealthResponse($getdecodeContents);
                    break;
                    // TODO : implement other LOBs
                default:
                    break;
            }

            return $getdecodeContents;
        } else {
            return 'API failed';
        }
    }

    private static function handleCarResponse($requestContent)
    {
        if (isset($data['carTypeInsuranceId']) && $data['carTypeInsuranceId'] != '') {
            $carQuote = CarQuote::where('uuid', $requestContent->quoteUID)->first();
            if ($carQuote) {
                $carQuote->cylinder = $data['cylinder'];
                $carQuote->seat_capacity = $data['seatCapacity'];
                $carQuote->vehicle_type_id = $data['vehicleTypeId'];
                $carQuote->is_quote_locked = true;
                $carQuote->car_model_detail_id = $data['trim'];
                $carQuote->car_value_tier = $data['carValueTier'];
                $carQuote->auto_assigned = null;
                $carQuote->assignment_type = null;
                $carQuote->save();

                if ($carQuote->advisor_id != null) {
                    $carQuote->quote_batch_id = QuoteBatches::latest()->first()->id;
                    $carQuote->save();

                    $upsertRecord = CarQuoteRequestDetail::updateOrCreate(
                        ['car_quote_request_id' => $carQuote->id],
                        [
                            'advisor_assigned_date' => now(),
                            'advisor_assigned_by_id' => auth()->id() ?? User::where('name', UserNameEnum::System)->first(),
                        ]
                    );

                    info('handleCarResponse - leadId : '.$carQuote->id.' - CarQuoteRequestDetail - created: '.$upsertRecord->wasRecentlyCreated);
                }
            }
        }
    }

    private static function handleHealthResponse($requestContent)
    {
        if (isset($requestContent->quoteUID)) {
            $healthQuote = HealthQuote::where('uuid', $requestContent->quoteUID)->first();
            if ($healthQuote) {
                HealthQuoteRequestDetail::updateOrCreate(
                    ['health_quote_request_id' => $healthQuote->id],
                    [
                        'advisor_assigned_date' => now(),
                        'advisor_assigned_by_id' => auth()->user()->id ?? User::where('name', UserNameEnum::System)->first(),
                    ]
                );
            }
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

    public static function getPersonalQuoteUUID($type)
    {
        $response = self::sendCAPIRequest('/api/v1-get-personal-quote-uuid', ['quoteTypeId' => $type]);
        if ($response) {
            return $response;
        } else {
            return false;
        }
    }
}
