<?php

namespace App\Enums;

use App\Models\LeadAllocation;
use App\Models\User;

enum LeadAllocationUserBLStatusFiltersEnum: string
{
    use Enumable;

    case ALL = 'all';
    case BUY_LEAD_ENABLED = 'buy-lead-enabled';
    case BUY_LEAD_DISABLED = 'buy-lead-disabled';

    public function applyFilter($data)
    {
        $users = User::select('id')->whereIn('id', $data->pluck('userId')->toArray())->get()->mapWithKeys(function ($user) {
            return [$user->id => $user];
        });

        return $data->filter(function (User|LeadAllocation $record) use ($users) {
            $freshUser = $users[$record->userId];

            return $this === self::BUY_LEAD_ENABLED ?
            ($freshUser->can(PermissionsEnum::BUY_LEADS) || $freshUser->hasPermissionTo(PermissionsEnum::BUY_LEADS)) :
            ($freshUser->cannot(PermissionsEnum::BUY_LEADS) || ! $freshUser->hasPermissionTo(PermissionsEnum::BUY_LEADS));
        })->values();
    }
}
