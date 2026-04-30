<?php

namespace App\Services;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\LeadAllocationController;
use App\Models\LeadAllocation;
use Illuminate\Http\Request;

class LeadAllocationPermissionService
{
    /**
     * Mutate operations require full allocation access: dashboard or edit (not view-only).
     * Travel also allows {@see PermissionsEnum::TRAVEL_SIC_ALLOCATION} (SIC / sales allocation).
     *
     * Authorizes using the same effective LOB as `request('quoteType')`: non-empty `quoteType`
     * in the merged body/query takes precedence, otherwise the `{quoteType}` route parameter.
     */
    public static function authorizeMutateForRouteQuoteType(Request $request): void
    {
        $fromRoute = $request->route('quoteType');
        $fromInput = $request->input('quoteType');

        $routeStr = is_string($fromRoute) && $fromRoute !== '' ? $fromRoute : null;
        $inputStr = is_string($fromInput) && $fromInput !== '' ? $fromInput : null;

        $raw = $inputStr ?? $routeStr;
        if ($raw === null || $raw === '') {
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

        if (! auth()->user()->hasAnyRole([RolesEnum::Engineering, RolesEnum::Admin])) {

            if ($permissions === []) {
                abort(403, 'Unauthorized action.');
            }

            if (! auth()->user()->hasAnyPermission($permissions)) {
                abort(403, 'Unauthorized action.');
            }
        }

    }

    public static function authorizeMutateForQuoteTypeId(int $quoteTypeId): void
    {
        $quoteType = QuoteTypes::getName($quoteTypeId);
        self::authorizeMutateForQuoteType($quoteType);
    }

    /**
     * Authorize shared toggles that resolve the row like {@see LeadAllocationController::updateResetCapSwitch}
     * and {@see LeadAllocationController::updateBlStatus}: `isset(leadId)` targets that id, otherwise `user_id`.
     * Extra keys such as `laId` are ignored by those controllers and must not affect authorization.
     */
    public static function authorizeMutateForSharedToggleLeadOrUser(Request $request): void
    {
        $leadAllocation = self::resolveLeadAllocationForLeadOrUser($request);
        self::authorizeMutateForResolvedSharedToggleRow($leadAllocation);
    }

    /**
     * Authorize shared toggles that resolve the row like {@see LeadAllocationController::updateNormalLeadAllocationStatus}
     * and {@see LeadAllocationController::updateBLResetCap}: `isset(laId)` targets that id, otherwise `user_id`.
     * Extra keys such as `leadId` are ignored by those controllers and must not affect authorization.
     */
    public static function authorizeMutateForSharedToggleLaOrUser(Request $request): void
    {
        $leadAllocation = self::resolveLeadAllocationForLaOrUser($request);
        self::authorizeMutateForResolvedSharedToggleRow($leadAllocation);
    }

    private static function resolveLeadAllocationForLeadOrUser(Request $request): ?LeadAllocation
    {
        $query = LeadAllocation::query()->with(['leadAllocationUser']);

        if (isset($request->leadId)) {
            $query->where('id', $request->leadId);
        } elseif (isset($request->userId)) {
            $query->where('user_id', $request->userId);
        } else {
            return null;
        }

        return $query->latest()->first();
    }

    private static function resolveLeadAllocationForLaOrUser(Request $request): ?LeadAllocation
    {
        $query = LeadAllocation::query()->with(['leadAllocationUser']);

        if (isset($request->laId)) {
            $query->where('id', $request->laId);
        } elseif (isset($request->userId)) {
            $query->where('user_id', $request->userId);
        } else {
            return null;
        }

        return $query->latest()->first();
    }

    private static function authorizeMutateForResolvedSharedToggleRow(?LeadAllocation $leadAllocation): void
    {
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
            /**
             * `lead_allocation.quote_type_id` 5 is BUSINESS; Corpline/Group Medical rows use this id.
             * Accept either LOB's dashboard|edit so shared toggles authorize correctly.
             */
            QuoteTypes::BUSINESS => [
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT,
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD,
                PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT,
            ],
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
