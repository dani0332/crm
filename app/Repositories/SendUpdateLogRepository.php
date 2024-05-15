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

            $count = $this->fetchGetCount($data['quote_uuid'], $data['quote_type_id']);

            $code = $code.'-'.date('m').date('y').'-'.($count + 1);

            $uuid = strtoupper(Str::random(6));

            $personalQuote = PersonalQuoteRepository::where([
                'quote_type_id' => $data['quote_type_id'],
                'uuid' => $data['quote_uuid'],
            ])->first();

            $data['personal_quote_id'] = $personalQuote?->id ?? null;

            $res = $this->create([
                'personal_quote_id' => $data['personal_quote_id'],
                'quote_uuid' => $data['quote_uuid'],
                'quote_type_id' => $data['quote_type_id'],
                'category_id' => $data['childCategory']['id'],
                'option_id' => $data['option_id'],
                'status' => $data['status'],
                'uuid' => $uuid,
                'code' => $code,
            ]);
        } catch (\Throwable $th) {
            $res = (object) [
                'message' => $th->getMessage(),
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
        } catch (\Exception $ex) {
            $log = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $log;
    }

    public function fetchGetCount($uuid, $quoteTypeId)
    {
        return $this->where([
            'quote_uuid' => $uuid,
            'quote_type_id' => $quoteTypeId,
        ])->count();
    }

    public function fetchFindByQuoteUuid($uuid)
    {
        return $this->where(['quote_uuid' => $uuid])->get();
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
                'status' => SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS,
            ]);
        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $result;
    }

    public function fetchSavePolicyDetails($data)
    {
        try {
            $result = $this->where('id', $data['id'])->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'provider_name' => $data['provider_name'],
                'plan_name' => $data['plan_name'],
                'policy_number' => $data['policy_number'],
                'issuance_date' => $data['issuance_date'],
                'start_date' => $data['start_date'],
                'expiry_date' => $data['expiry_date'],
                'insurer_quote_number' => $data['insurer_quote_number'],
                'issuance_status_id' => $data['issuance_status_id'],
                'status' => SendUpdateLogStatusEnum::REQUEST_IN_PROGRESS,
            ]);
        } catch (\Exception $ex) {
            $result = (object) [
                'message' => $ex->getMessage(),
            ];
        }

        return $result;
    }

    public function fetchEndorsementsByPersonalQuoteId($personalQuoteId)
    {
        return $this->where('personal_quote_id', $personalQuoteId)->where(function ($q) {
            $q->where('code', 'like', '%EF%')->orWhere('code', 'like', '%EN%');
        })->orderBy('id', 'desc')->get();
    }
}
