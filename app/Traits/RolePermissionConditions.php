<?php

namespace App\Traits;

use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use Auth;

trait RolePermissionConditions
{
    use GetUserTreeTrait;

    public function whereBasedOnRole($query, $prefix, $restrictedQuoteType = null)
    {
        $isRenewalAdvisor = Auth::user()->isRenewalAdvisor();
        $isRenewalManager = Auth::user()->isRenewalManager();
        $isNewManager = Auth::user()->isNewBusinessManager();
        $isNewAdvisor = Auth::user()->isNewBusinessAdvisor();
        $isHealthManager = Auth::user()->isHealthManager();
        $isCarManager = Auth::user()->isCarManager();
        $isCarAdvisor = Auth::user()->isCarAdvisor();
        $isAdvisor = Auth::user()->isAdvisor();
        $isAdmin = Auth::user()->isAdmin();

        if ($isRenewalAdvisor) {

            $query->whereNotNull($prefix.'.'.'previous_quote_policy_number');
            $query->where($prefix.'.'.'advisor_id', Auth::user()->id);
        }
        if ($isRenewalManager) {
            $ids = $this->walkTree(Auth::user()->id);
            $query->whereNotNull($prefix.'.'.'previous_quote_policy_number');
            $query->whereIn($prefix.'.'.'advisor_id', $ids);
        }
        if ($isNewAdvisor) {
            $query->where($prefix.'.'.'advisor_id', Auth::user()->id);
            $query->whereNull($prefix.'.'.'previous_quote_policy_number');
        }
        if ($isAdvisor) {
            $query->where($prefix.'.'.'advisor_id', Auth::user()->id);
        }
        if ($isNewManager) {
            $ids = $this->walkTree(Auth::user()->id);
            //    $query->whereIn($prefix.'.'.'advisor_id', $ids);
            $query->whereNull($prefix.'.'.'previous_quote_policy_number');
        }
        if ($isHealthManager && $restrictedQuoteType == quoteTypeCode::Health) {

            $productTeam = $this->getProductByName(quoteTypeCode::Car);
            $carTeams = $this->getTeamsByProductId($productTeam->id)->pluck('id');
            $carUserIds = [];

            foreach ($carTeams as $carTeam) {
                if (empty($carUserIds)) {
                    $carUserIds = $this->getUsersByTeamId($carTeam)->pluck('id')->toArray();
                } else {
                    $carUserIds = array_merge($carUserIds, $this->getUsersByTeamId($carTeam)->pluck('id')->toArray());
                }
            }
            // This condition allows cross-LOB access if a user possesses two roles, such as health manager and car manager.
            if (! ($isHealthManager && $restrictedQuoteType == quoteTypeCode::Health)) {
                $query->where(function ($qry) use ($prefix, $carUserIds) {
                    $qry->whereNotIn($prefix.'.'.'advisor_id', $carUserIds)
                        ->OrWhereNull($prefix.'.'.'advisor_id');
                });
            }
            // This condition allows cross-LOB access if a user possesses two roles, such as health manager and car manager.
            if ($isHealthManager && $restrictedQuoteType == quoteTypeCode::Health && ! $isAdmin) {
                $ids = $this->associateAdvisorsWithManager(Auth::user()->id);
                $ids[] = Auth::user()->id;
                $query->whereIn($prefix.'.'.'advisor_id', $ids);
            }
        }
        if ($isCarManager && $restrictedQuoteType == quoteTypeCode::Health && Auth::user()->can(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
            $ids = $this->walkTree(Auth::user()->id, quoteTypeCode::Car);
            $query->whereIn($prefix.'.'.'advisor_id', $ids);
        }
        if ($isCarAdvisor && $restrictedQuoteType == quoteTypeCode::Health && Auth::user()->can(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
            $query->where($prefix.'.'.'advisor_id', Auth::user()->id);
        }
    }
}
