<?php

namespace App\Repositories;

use App\Models\SendUpdateLog;
use Illuminate\Support\Str;

class SendUpdateLogRepository extends BaseRepository
{

    public function model()
    {
        return SendUpdateLog::class;
    }

    public function fetchCreate($data) 
    {
        try {
            $code = $data['childCategory']['slug'];

            $count = $this->fetchGetCount($data['reportable_id']);

            $code = $code . '-' . date('m') . date('y') . '-' . ($count + 1);

            $uuid = strtoupper(Str::random(6));

            $res = $this->create([
                'reportable_type' => $data['reportable_type'],
                'reportable_id' => $data['reportable_id'],
                'quote_type_id' => $data['quote_type_id'],
                'category_id' => $data['childCategory']['id'],
                'option_id' => $data['option'],
                'status' => $data['status'],
                'uuid' => $uuid,
                'code' => $code
            ]);            
        } catch (\Throwable $th) {
            $res = (object) [
                'message' => $th->getMessage()
            ];
        }

        return $res;
    }

    public function fetchGetLogByUuid($uuid)
    {
        return $this->where('uuid', $uuid)->firstOrFail();
    }

    public function fetchGetLogsById($id)
    {
        return $this->where('id', $id)->get();
    }

    public function fetchUpdateLog($id, $data)
    {
        try {
            $log = $this->where('id', $id)->update([
                'notes' => $data['notes'],
                'option_id' => $data['option'],
            ]);
        } catch(\Exception $ex) {
            $log = (object) [
                'message' => $ex->getMessage()
            ];
        }

        return $log;
    }

    public function fetchGetCount($id) 
    {
        return $this->where(['reportable_id' => $id])->count();
    }

    public function fetchFindByQuoteId($reportableTye, $reportableId) 
    {   
        return $this->where([
            'reportable_type' => "App\\Models\\$reportableTye",
            'reportable_id' => $reportableId
        ])->get();
    }
}
