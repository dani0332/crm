<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

class HealthAvailablePlan extends Model
{
    use Sushi;

    protected $cast = [
        'id' => 'string',
        'planCode' => 'string',
        'name' => 'string',
        'isRatingAvailable' => 'boolean',
        'isNorthern' => 'boolean',
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
    public $recordId;

    public function setRecordID($recordId)
    {
        $this->recordId = $recordId;

        return $this->query();
    }

    /**
     * Model Rows.
     *
     * @return void
     */
    public function getRows()
    {
        $listQuotePlans = '';
        $quotePlans = HealthQuotePlan::where('health_quote_request_id', $this->recordId)->first();

        if ($quotePlans) {
            $listQuotePlans = json_decode($quotePlans->plan_payload, true)['plans'];
        }

        return collect($listQuotePlans)->map(function ($plan) {
            return [
                    'id' => $plan['id'],
                    'planCode' => $plan['planCode'],
                    'name' => $plan['name'],
                    // 'isRatingAvailable' => $plan['isRatingAvailable'],
                    // 'isNorthern' => $plan['isNorthern'],
                    'actualPremium' => $plan['actualPremium'],
                    'basmah' => $plan['basmah'],
                    'vat' => $plan['vat'],
                    'discountPremium' => $plan['discountPremium'],
                    'memberPremiumBreakdown' => json_encode($plan['memberPremiumBreakdown']),
                    'providerId' => $plan['providerId'],
                    'providerCode' => $plan['providerCode'],
                    'providerName' => $plan['providerName'],
                    // 'addons' => $plan['addons'],
                    // 'benefits' => $plan['benefits'],
                    // 'policyWordings' => $plan['policyWordings'],
                    // 'excess' => $plan['excess'],
            ];
        })->all();
    }
}
