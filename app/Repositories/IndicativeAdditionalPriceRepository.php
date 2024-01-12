<?php

namespace App\Repositories;

use App\Models\HealthRatingEligibility;
use App\Models\IndicativeAdditionalPrice;
use Illuminate\Support\Facades\DB;

class IndicativeAdditionalPriceRepository extends BaseRepository
{
    public function model()
    {
        return IndicativeAdditionalPrice::class;
    }

    public function fetchGetBySendUpdateLogId($id) 
    {
        try {
            return $this->where('send_update_log_id', '=', $id)->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}
