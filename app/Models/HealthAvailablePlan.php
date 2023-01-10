<?php

namespace App\Models;

use App\Enums\GenericRequestEnum;
use App\Services\HealthQuoteService;
use Illuminate\Database\Eloquent\Model;
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
    public $uuid;

    /**
     * Model Rows.
     *
     * @return void
     */
    public function getRows()
    {
        $this->uuid = request()->route('record');

        $listQuotePlans = '';
        $responseData = [];
        $quotePlans = app(HealthQuoteService::class)->getQuotePlansNew($this->uuid);

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) !== GenericRequestEnum::TypeString) {
                $listQuotePlans = $quotePlans->quote->plans;
                foreach ($listQuotePlans as $key => $plan) {
                    $responseData[] = [
                        'uuid' => $this->uuid,
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
                }

                return $responseData;
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
