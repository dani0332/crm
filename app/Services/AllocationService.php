<?php

namespace App\Services;

use App\Models\ApplicationStorage;
use App\Models\Tier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    public function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public function getTierById($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    public function updateLeadAllocationCounts($userId): void
    {
        $timestamp = Carbon::now()->timestamp;

        DB::table('lead_allocation')
            ->where('user_id', $userId)
            ->update([
                'allocation_count' => DB::raw('allocation_count + 1'),
                'auto_assignment_count' => DB::raw('auto_assignment_count + 1'),
                'last_allocated' => $timestamp,
                'updated_at' => now(),
            ]);
    }

    public function getValuation($carModelDetailId, $yearOfManufacture)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/get-vehicle-value';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');

        $client = new \GuzzleHttp\Client();
        $request = $client->post(
            $apiEndPoint,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $apiToken],
                'body' => json_encode([
                    'carModelDetailId' => $carModelDetailId,
                    'yearOfManufacture' => $yearOfManufacture,
                ]),
                'timeout' => $apiTimeout,
            ]
        );

        $getStatusCode = $request->getStatusCode();

        if ($getStatusCode == 200) {
            $getContents = $request->getBody();
            $getdecodeContents = json_decode($getContents);

            return $getdecodeContents;
        } else {
            info(' call to ken api failed for getting car valuation ');

            return 'API failed';
        }
    }
}
