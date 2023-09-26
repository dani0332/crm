<?php

namespace App\Traits;

use App\Models\Tier;

trait TierTrait
{

    public function getTiers($condition = [])
    {
        return $this->resolveQuery(Tier::where('is_active', true)->where($condition));
    }

    public function getTier($condition, $action = 'get')
    {
        return $this->resolveQuery(Tier::where('is_active', true)->where($condition), $action);
    }

    public function resolveQuery($query, $action = 'get')
    {
        if ($action === 'first') {
            return $query->first();
        } else {
            return $query->get();
        }
    }

}
