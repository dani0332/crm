<?php

namespace App\Repositories;

use App\Models\SendUpdateLog;
use Illuminate\Support\Str;

class SendUpdateLogRepository extends BaseRepository
{
    /**
     * 
     * 
     */
    public function model()
    {
        return SendUpdateLog::class;
    }

    /**
     * 
     * 
     */
    public function fetchCreate($request) 
    {        
        try {
            $code = $request['childCategory']['slug'];

            $uuid = Str::limit(base64_encode(now()), 5);

            $res = $this->create([
                'reportable_type' => $request['reportable_type'],
                'reportable_id' => $request['reportable_id'],
                'quote_type_id' => $request['quote_type_id'],
                'category_id' => $request['childCategory']['id'],
                'option_id' => $request['option'],
                'status' => $request['status'],
                'uuid' => $uuid,
                'code' => $code . '-' . $uuid
            ]);            
        } catch (\Throwable $th) {
            $res = (object) [
                'message' => $th->getMessage()
            ];
        }

        return $res;
    }

    /**
     * 
     * 
     */
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
                'option_id' => $data['option_id'],
            ]);
        } catch(\Exception $ex) {
            $log = (object) [
                'message' => $ex->getMessage()
            ];
        }

        return $log;
    }
}
