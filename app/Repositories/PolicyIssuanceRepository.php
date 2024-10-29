<?php

namespace App\Repositories;

use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;

class PolicyIssuanceRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return PolicyIssuance::class;
    }

    public function fetchSchedulePolicyIssuance($quote, $insurer, $quoteType, $logFor)
    {
        $policyIssuance = $this->where(['model_type' => $quote->getMorphClass(), 'model_id' => $quote->id, 'quote_type' => $quoteType])->first();

        if ($policyIssuance) {
            info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule already exists PID : '.$policyIssuance->id);
        } else {
            $policyIssuance = $this->create([
                'insurance_provider_id' => $insurer->id, 'model_type' => $quote->getMorphClass(), 'model_id' => $quote->id, 'quote_type' => $quoteType, 'status' => PolicyIssuanceEnum::PENDING_STATUS,
            ]);
            info('automation:'.$logFor.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Policy Issuance Schedule created PID : '.$policyIssuance->id);
        }
    }

    public function fetchPolicyIssuanceByStatus($status)
    {
        return $this->where('status', $status)->orderBy('created_at')->get();
    }

}
