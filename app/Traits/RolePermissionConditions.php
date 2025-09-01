<?php

namespace App\Traits;

use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Models\User;
use Auth;

trait RolePermissionConditions
{
    use GetUserTreeTrait;

    public function whereBasedOnRole($query, $prefix, $restrictedQuoteType = null, ?User $user = null)
    {
        if (Auth::check() && empty($user)) {
            $user = Auth::user();
        }

        $isRenewalAdvisor = $user->isRenewalAdvisor();
        $isRenewalManager = $user->isRenewalManager();
        $isNewManager = $user->isNewBusinessManager();
        $isNewAdvisor = $user->isNewBusinessAdvisor();
        $isHealthManager = $user->isHealthManager();
        $isCarManager = $user->isCarManager();
        $isCarAdvisor = $user->isCarAdvisor();
        $isAdvisor = $user->isAdvisor();
        $isSupportUser = $user->isSupportUser();
        $isAdmin = $user->isAdmin();

        if ($isRenewalAdvisor) {

            $query->whereNotNull($prefix.'.'.'previous_quote_policy_number');
            $query->where($prefix.'.'.'advisor_id', $user->id);
        }
        if ($isRenewalManager) {
            $ids = $this->walkTree($user->id, user: $user);
            $query->whereNotNull($prefix.'.'.'previous_quote_policy_number');
            $query->whereIn($prefix.'.'.'advisor_id', $ids);
        }
        if ($isNewAdvisor) {
            $query->where($prefix.'.'.'advisor_id', $user->id);
            $query->whereNull($prefix.'.'.'previous_quote_policy_number');
        }
        if ($isAdvisor) {
            $query->where($prefix.'.'.'advisor_id', $user->id);
        }
        if ($isSupportUser && $restrictedQuoteType === quoteTypeCode::Business) {
            // Only apply support_user_id filter for quote types that have this column
            // Currently only Business quotes have support_user_id column
            $query->where($prefix.'.'.'support_user_id', $user->id);
        }
        if ($isNewManager) {
            $this->walkTree($user->id, user: $user);
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
                $ids = $this->associateAdvisorsWithManager($user->id);
                $ids[] = $user->id;
                $query->whereIn($prefix.'.'.'advisor_id', $ids);
            }
        }
        if ($isCarManager && $restrictedQuoteType == quoteTypeCode::Health && $user->can(PermissionsEnum::HEALTH_QUOTES_MANAGER_ACCESS)) {
            $ids = $this->walkTree($user->id, quoteTypeCode::Car);
            $query->whereIn($prefix.'.'.'advisor_id', $ids);
        }
        if ($isCarAdvisor && $restrictedQuoteType == quoteTypeCode::Health && $user->can(PermissionsEnum::HEALTH_QUOTES_ACCESS)) {
            $query->where($prefix.'.'.'advisor_id', $user->id);
        }
    }
}
