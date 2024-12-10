<?php

namespace App\Traits;

use App\Enums\WatermarkDocTypesEnum;
use App\Models\InsuranceProvider;

trait GetWatermarkPropertyTrait
{
    public function getWatermarkProperty($quote, $documentType, $insuranceProviderId = null)
    {
        $ips = InsuranceProvider::where('skip_watermark', 1)->select('id')->pluck('id')->toArray();

        if ($insuranceProviderId) {
            $skipWatermark = in_array($insuranceProviderId, $ips);
        } else {
            $skipWatermark = in_array($quote->insurance_provider_id, $ips);
        }

        if (! $skipWatermark && in_array($documentType->code, WatermarkDocTypesEnum::asArray())) {
            return true;
        }

        return false;
    }
}
