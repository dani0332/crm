<?php

namespace App\Models;

use App\Enums\GenericRequestEnum;
use App\Services\HealthQuoteService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class HealthAvailablePlan extends Model
{
    use Sushi;

    public $uuid;

    /**
     * Model Rows.
     *
     * @return void
     */
    public function getRows()
    {
        // request pathInfo
        $pathInfo = request()->getPathInfo();
        $pathInfo = explode('/', $pathInfo);
        $this->uuid = $pathInfo[3];

        $listQuotePlans = '';
        $responseData = [];
        $quotePlans = app(HealthQuoteService::class)->getQuotePlansNew($this->uuid);

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) !== GenericRequestEnum::TypeString) {
                $listQuotePlans = $quotePlans->quote->plans;

                $responseData = collect($listQuotePlans)->map(function ($plan) {
                    return [
                        'id' => $plan?->id,
                        'planCode' => $plan?->planCode,
                        'name' => $plan?->name,
                        'actualPremium' => $plan?->actualPremium,
                        'basmah' => $plan?->basmah,
                        'vat' => $plan?->vat,
                        'discountPremium' => $plan?->discountPremium,
                        'memberPremiumBreakdown' => json_encode($plan?->memberPremiumBreakdown),
                        'providerId' => $plan?->providerId,
                        'providerCode' => $plan?->providerCode,
                        'providerName' => $plan?->providerName,
                        'addons' => json_encode($plan?->addons),
                        'benefits' => json_encode($plan?->benefits),
                        'policyWordings' => json_encode($plan?->policyWordings),
                        'excess' => json_encode($plan?->excess),
                    ];
                })->all();
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $responseData;
    }

    protected function sushiShouldCache()
    {
        return true;
    }
}
