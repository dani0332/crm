<?php
namespace App\Traits;
use App\Traits\GetUserTree;
use Auth;
trait RolePermissionConditions {

    use GetUserTree;

    public function whereBasedOnRole($query, $prefix) {
        $isRenewalAdvisor = Auth::user()->isRenewalAdvisor();
        $isRenewalManager = Auth::user()->isRenewalManager();
        $isNewManager = Auth::user()->isNewBusinessManager();
        $isNewAdvisor = Auth::user()->isNewBusinessAdvisor();
        $isAdvisor = Auth::user()->isAdvisor();
        $ids = $this->walkTree(Auth::user()->id);

        if ($isRenewalAdvisor) {
            $query->whereNotNull($prefix .'.'. 'previous_quote_id');
            $query->where($prefix .'.'. 'advisor_id', Auth::user()->id);
        }
        if ($isRenewalManager) {
            $query->whereNotNull($prefix .'.'. 'previous_quote_id');
            $query->whereIn($prefix .'.'. 'advisor_id', $ids);
        }
        if ($isNewAdvisor) {
            $query->where($prefix .'.'. 'advisor_id', Auth::user()->id);
            $query->whereNull($prefix .'.'. 'previous_quote_id');
        }
        if ($isNewManager) {
            $query->whereIn($prefix .'.'. 'advisor_id', $ids);
            $query->whereNull($prefix .'.'. 'previous_quote_id');
        }
    }
}
