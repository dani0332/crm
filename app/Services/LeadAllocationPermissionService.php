<?php

namespace App\Services;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\LeadAllocation;
use Illuminate\Http\Request;

class LeadAllocationPermissionService
{
    /**
     * Mutate operations require full allocation access: dashboard or edit (not view-only).
     * Travel also allows {@see PermissionsEnum::TRAVEL_SIC_ALLOCATION} (SIC / sales allocation).
     */
    public static function authorizeMutateForRouteQuoteType(Request $request): void
    {
        $raw = $request->route('quoteType') ?? $request->input('quoteType');
        if (! is_string($raw) || $raw === '') {
            abort(403, 'Unauthorized action.');
        }

        $id = QuoteTypes::getIdFromValue($raw);
        if ($id === null) {
            abort(403, 'Unauthorized action.');
        }

        $quoteType = QuoteTypes::getName($id);
        self::authorizeMutateForQuoteType($quoteType);
    }

    public static function authorizeMutateForQuoteType(?QuoteTypes $quoteType): void
    {
        $permissions = self::mutatePermissionsForQuoteType($quoteType);
        if ($permissions === []) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->hasAnyPermission($permissions)) {
            abort(403, 'Unauthorized action.');
        }
    }

    public static function authorizeMutateForQuoteTypeId(int $quoteTypeId): void
    {
        $quoteType = QuoteTypes::getName($quoteTypeId);
        self::authorizeMutateForQuoteType($quoteType);
    }

    /**
     * Shared POST routes (toggle-reset-cap, etc.) are used by multiple LOB UIs. Resolve
     * the lead allocation row the same way as the controller, then require dashboard|edit
     * for that LOB.
     *
     * Matches controller resolution order: leadId, laId, or latest row for userId (see
     * LeadAllocationController updateResetCapSwitch and related methods).
     */
    public static function authorizeMutateForSharedToggleRequest(Request $request): void
    {
        $query = LeadAllocation::query()->with(['leadAllocationUser']);

        if ($request->filled('leadId')) {
            $query->where('id', $request->leadId);
        } elseif ($request->filled('laId')) {
            $query->where('id', $request->laId);
        } elseif ($request->filled('userId')) {
            $query->where('user_id', $request->userId);
        } else {
            abort(403, 'Unauthorized action.');
        }

        $leadAllocation = $query->latest()->first();
        if ($leadAllocation === null) {
            abort(403, 'Unauthorized action.');
        }

        self::authorizeMutateForQuoteTypeId((int) $leadAllocation->quote_type_id);
    }

    /**
     * @return list<string>
     */
    public static function mutatePermissionsForQuoteType(?QuoteTypes $quoteType): array
    {
        if ($quoteType === null) {
            return [];
        }

        return match ($quoteType) {
            QuoteTypes::CAR => [
                PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::HEALTH => [
                PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::HOME => [
                PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::HOME_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::LIFE => [
                PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::LIFE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::PET => [
                PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::PET_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::CYCLE => [
                PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CYCLE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::YACHT => [
                PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::YACHT_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::TRAVEL => [
                PermissionsEnum::TRAVEL_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT,
                PermissionsEnum::TRAVEL_SIC_ALLOCATION,
            ],
            QuoteTypes::CORPLINE => [
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::GROUP_MEDICAL => [
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::SAVINGS => [
                PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::SAVINGS_LEAD_ALLOCATION_EDIT,
            ],
            QuoteTypes::CYBER => [
                PermissionsEnum::CYBER_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CYBER_LEAD_ALLOCATION_EDIT,
            ],
            default => [],
        };
    }

    /**
     * Whether the current user may change allocation state (toggles, caps, master switches):
     * dashboard or edit (or travel SIC), but not view-only.
     */
    public static function userCanMutate(?QuoteTypes $quoteType): bool
    {
        if ($quoteType === null) {
            return false;
        }
        if (auth()->user()->hasAnyRole([RolesEnum::Engineering, RolesEnum::Admin])) {
            return true;
        }

        $permissions = self::mutatePermissionsForQuoteType($quoteType);
        if ($permissions === []) {
            return false;
        }

        return auth()->user()->hasAnyPermission($permissions);
    }
}
