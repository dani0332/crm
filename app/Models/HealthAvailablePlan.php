<?php

namespace App\Models;

use App\Enums\GenericRequestEnum;
use App\Services\HealthQuoteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Sushi\Sushi;

class HealthAvailablePlan extends Model
{
    use Sushi;

    protected $table = 'health_available_plans';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $schema = [
        'id' => 'integer',
        'uuid' => 'string',
        'planCode' => 'string',
        'name' => 'string',
        'actualPremium' => 'string',
        'basmah' => 'string',
        'vat' => 'string',
        'discountPremium' => 'string',
        'memberPremiumBreakdown' => 'string',
        'providerId' => 'string',
        'providerCode' => 'string',
        'providerName' => 'string',
        'addons' => 'string',
        'benefits' => 'string',
        'policyWordings' => 'string',
        'excess' => 'string',
    ];
    protected $casts = [
        'memberPremiumBreakdown' => 'array',
        'addons' => 'array',
        'benefits' => 'array',
        'policyWordings' => 'array',
        'excess' => 'array',
    ];

    /**
     * Model Rows.
     *
     * @return void
     */
    public function getRows()
    {
        return Cache::remember('HealthAvailablePlan::rows', now()->addMinutes(5), function () {
            return $this->getAPI();
        });
    }

    protected function getAPI()
    {
        $listQuotePlans = '';
        $responseData = [];
        $quotePlans = app(HealthQuoteService::class)->getQuotePlansNew(last(request()->segments()));

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) !== GenericRequestEnum::TypeString) {
                $listQuotePlans = $quotePlans->quote->plans;

                $responseData = collect($listQuotePlans)->map(function ($plan) {
                    return [
                        'uuid' => last(request()->segments()),
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
                })->toArray();
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $responseData;
    }

    // protected function sushiShouldCache()
    // {
    //     return true;
    // }
}
