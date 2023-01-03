<?php

namespace App\Models;

use App\Services\HealthQuoteService;
use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class HealthAvailablePlan extends Model
{
    use Sushi;

    protected $schema = [
        'id' => 'integer',
        'actualPremium' => 'float',
        'basmah' => 'float',
        'vat' => 'float',
        'discountPremium' => 'float',
        'benefits' => 'json',
        'excess' => 'json',
        'addons' => 'json',
        'memberPremiumBreakdown' => 'json',
        'policyWordings' => 'json',
    ];
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
                'id' => $plan->id ?? null,
                'planCode' => $plan->planCode ?? '',
                'name' => $plan->name ?? '',
                'actualPremium' => $plan->actualPremium ?? null,
                'basmah' => $plan->basmah ?? null,
                'vat' => $plan->vat ?? null,
                'discountPremium' => $plan->discountPremium ?? null,
                'memberPremiumBreakdown' => json_encode($plan->memberPremiumBreakdown ?? ''),
                'providerId' => $plan->providerId ?? null,
                'providerCode' => $plan->providerCode ?? '',
                'providerName' => $plan->providerName ?? '',
                'addons' => json_encode($plan->addons ?? ''),
                'benefits' => json_encode($plan->benefits ?? ''),
                'policyWordings' => json_encode($plan->policyWordings ?? ''),
                'excess' => json_encode($plan->excess ?? ''),
            ];
        })->all();
        // dd($data);

        return $data;
    }

    protected function sushiShouldCache()
    {
        return true;
    }
}
