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

    public function fetchCreate($request) 
    {        
        try {
            $name = $request['childCategory']['title'];
            $code = '';
            collect(explode(" ", $name))->each(function ($word) use (&$code) {
                $code .= mb_substr(ucfirst($word), 0, 1);
            });

            $uuid = Str::upper(Str::random(7)); // EF-JHU12L1

            $res = $this->create([
                'reportable_type' => $request['reportable_type'],
                'reportable_id' => $request['reportable_id'],
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
}
