<?php

namespace App\Repositories;

use App\Enums\SendUpdateLogStatusEnum;
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
                'personal_quote_id' => $data['personal_quote_id'],
                'reportable_id' => $data['reportable_id'],
                'reportable_uuid' => $data['reportable_uuid'],
                'quote_type_id' => $data['quote_type_id'],
                'category_id' => $data['childCategory']['id'],
                'option_id' => $data['option_id'],
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

    public function fetchGetCount($id) 
    {
        return $this->fetchFindByQuoteId($id)->count();
    }

    public function fetchFindByQuoteId($reportableId) 
    {   
        return $this->where(['reportable_id' => $reportableId])->get();
    }

    public function fetchUpdateLogPriceDetails($data) 
    {
        try {
            $result = $this->where('id', $data['id'])->update([
                'total_price' => $data['total_price'],
                'price_with_vat' => $data['price_with_vat'],
                'price_without_vat' => $data['price_without_vat'],
                'insurer_quote_number' => $data['insurer_quote_number'],
                'insurance_provider_id' => $data['insurance_provider_id'],
                'status' => SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS
            ]);
        } catch(\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage()
            ];
        }

        return $result;
    }
}
