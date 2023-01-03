<?php

namespace App\Models;

use App\Services\HealthQuoteService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class HealthAvailablePlan extends Model
{
    use Sushi;

    protected function sushiShouldCache()
    {
        return true;
    }

    protected $keyType = 'string';
    public $uuid;
    protected $cast = [
        'id' => 'string',
        'planCode' => 'string',
        'name' => 'string',
        'actualPremium' => 'float',
        'basmah' => 'float',
        'vat' => 'float',
        'discountPremium' => 'float',
        'memberPremiumBreakdown' => 'array',
        'providerId' => 'string',
        'providerCode' => 'string',
        'providerName' => 'string',
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
        // request pathInfo
        $pathInfo = request()->getPathInfo();
        $pathInfo = explode('/', $pathInfo);
        $this->uuid = $pathInfo[3];

        $listQuotePlans = '';
        $quotePlans = app(HealthQuoteService::class)->getQuotePlansNew($this->uuid);

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string') {
                $listQuotePlans = $quotePlans->quote->plans;
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        $data = collect($listQuotePlans)->map(function ($plan) {
            return [
                'id' => $plan->id ?? '',
                'planCode' => $plan->planCode ?? '',
                'name' => $plan->name ?? '',
                'actualPremium' => $plan->actualPremium ?? '',
                'basmah' => $plan->basmah ?? '',
                'vat' => $plan->vat ?? '',
                'discountPremium' => $plan->discountPremium ?? '',
                'memberPremiumBreakdown' => json_encode($plan->memberPremiumBreakdown ?? '') ?? '',
                'providerId' => $plan->providerId ?? '',
                'providerCode' => $plan->providerCode ?? '',
                'providerName' => $plan->providerName ?? '',
                'addons' => json_encode($plan->addons ?? '') ?? '',
                'benefits' => json_encode($plan->benefits ?? '') ?? '',
                'policyWordings' => json_encode($plan->policyWordings ?? '') ?? '',
                'excess' => json_encode($plan->excess ?? '') ?? '',
            ];
        })->all();

        return $data;
    }
}
