<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    /**
     * return response
     *
     * @param  [type] $data
     * @param  [type] $statusCode
     * @return [type]
     */
    public function respond($data, $statusCode = Response::HTTP_OK)
    {
        return response()->json($data, $statusCode, []);
    }

    /**
     * response for error
     *
     * @param  Exception  $e
     * @return Response Json
     */
    public function respondFatalError($e)
    {
        $message = $e->getMessage();
        $errorCode = $e->getCode();

        if ($errorCode === 0 || $errorCode === 1) {
            $errorCode = 422;
        }

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->respondValidationError($e->validator, $errorCode);
        }

        if ($e instanceof ValidationException) {
            $exception = $e->getPrevious();
            if ($exception) {
                $error = json_decode($exception->getResponse()->getBody(), true);
                $message = arrayKeyExists('message', $error);
            }
        }

        return $this->respondError($message, $errorCode, $e);
    }

    /**
     * response for error
     *
     * @param  Exception  $e
     * @return Response Json
     */
    public function respondError($message, $errorCode = Response::HTTP_UNPROCESSABLE_ENTITY, $e = null)
    {
        $response = [
            'error' => true,
            'message' => $message,
        ];

        if (env('APP_DEBUG') && $e) {
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
        }

        return response()->json($response, $errorCode, []);
    }

    public function respondSuccess($message = 'Success', $data = [])
    {
        $response = [
            'data' => $data,
            'message' => $message,
        ];

        return response()->json($response, Response::HTTP_OK, []);
    }

    /**
     * return response
     *
     * @param  [type] $data
     * @param  [type] $statusCode
     * @return [type]
     */
    public function respondData($data, $statusCode = Response::HTTP_OK)
    {
        $response = [
            'data' => $data,
        ];

        return response()->json($response, $statusCode, []);
    }

    /**
     * Format error response in json.
     *
     * @param  $e:  Exception
     * @return json.
     */
    public function getExceptionErrors($e)
    {
        $message = $e->getMessage();

        $status = ($e->getCode() == 0) ? Response::HTTP_UNPROCESSABLE_ENTITY : $e->getCode();
        $error = [
            'message' => $message,
            'status' => $e->getCode(),
            'line' => $e->getLine(),
        ];

        return response()->json($error, $status);
    }

    public function duplicateEntires()
    {
        DB::table('car_quote_request_detail_duplicate')
        ->select('car_quote_request_id', DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
        ->groupBy('car_quote_request_id')
        ->havingRaw('COUNT(*) > 1')
        ->whereNull('is_deleted')
        ->orderBy('car_quote_request_id')
        ->chunk(1000, function ($duplicates) {

            $allIds = $duplicates->pluck('ids')
            ->map(fn($ids) => explode(',', $ids))
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

            $mainQuery = DB::table('car_quote_request_detail_duplicate')
            ->whereIn('id', $allIds)->whereNull('is_deleted')->get();
            
            $lastGroupArray = [];
            
            foreach ($duplicates as $group) {
            
                $query = $mainQuery->whereIn('id', explode(',', $group->ids));
    
                $latestUpdatedRecord = $query->sortByDesc('updated_at')->first();
        
                $lastId = $latestUpdatedRecord->id;
                $lastRecordData = (array) $latestUpdatedRecord;

                $filteredRecords = $query->filter(function ($record) use ($lastId) {
                    return $record->id != $lastId;
                })->values();
                
                $updatedData = [];
                
                $recentAdvisorData = $query->sortByDesc('advisor_assigned_date')->first();

                if ($recentAdvisorData && !empty($recentAdvisorData)) {
                    $updatedData['advisor_assigned_by_id'] = $recentAdvisorData->advisor_assigned_by_id;
                    $updatedData['advisor_assigned_date'] = $recentAdvisorData->advisor_assigned_date;
                }
 
                foreach ($filteredRecords as $record) {
                    $currentRecord = (array) $record;

                    foreach ($lastRecordData as $column => $lastValue) {
                        if (!in_array($column, ['id', 'created_at', 'updated_at', 'car_quote_request_id', 'advisor_assigned_date', 'advisor_assigned_by_id'])) {
                            if ((empty($lastValue) || is_null($lastValue)) 
                                && !empty($currentRecord[$column]) 
                                && !is_null($currentRecord[$column]) 
                                && !array_key_exists($column, $updatedData)) {

                                $updatedData[$column] = $currentRecord[$column];
                            }
                        }
                    }
                }

                $updatedData = $updatedData + array_diff_key($lastRecordData, $updatedData);

                DB::table('car_quote_request_detail_duplicate')
                    ->where('id', $lastId)
                    ->update($updatedData);

                $array = explode(',', $group->ids);
                $filteredArray  = array_diff($array, [$lastId]);
                $lastGroupArray[] = implode(',', $filteredArray);      
            }

            $deleteAt = collect($lastGroupArray)
            ->map(fn($ids) => explode(',', $ids))
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

            DB::table('car_quote_request_detail_duplicate')
            ->whereIn('id', $deleteAt)
            ->update(['is_deleted' => true]);

            sleep(1);
        });
    }
}
