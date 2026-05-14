<?php

namespace App\Services;

use App\Enums\BranchEnum;
use App\Enums\EmirateEnum;
use App\Enums\QuoteTypeId;
use App\Models\Branch;
use App\Models\BranchOverride;
use App\Models\BranchOverrideConfig;
use App\Models\UserBranch;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;

class BranchAssignmentService extends BaseService
{
    private static $branches = [];
    private static $branchOverrideConfigs = [];

    public function __construct()
    {
        $this->loadBranchData();
    }

    private function loadBranchData()
    {
        if (empty(self::$branches)) {
            self::$branches = Branch::all();
        }

        if (empty(self::$branchOverrideConfigs)) {
            self::$branchOverrideConfigs = BranchOverrideConfig::active()->get();
        }
    }

    public function getGridData($request)
    {
        $dataset = UserBranch::with('user', 'user.usersroles', 'branch')
            ->whereHas('user', function ($query) {
                $query->whereHas('usersroles', function ($query) {
                    $query->where('name', 'like', '%advisor%');
                });
            })
            ->when(! empty($request['advisors']), function ($query) use ($request) {
                $query->whereIn('user_id', $request['advisors']);
            })
            ->when(! empty($request['primary_branch']), function ($query) use ($request) {
                $query->where('branch_id', $request['primary_branch'])
                    ->where('is_primary', 1);
            })
            ->where('status', 1)
            ->paginate();

        $dataset->map(function ($item) {

            $item->roles = $item->user->usersroles
                ->pluck('name')
                ->implode(', ');

            return $item;
        });

        return $dataset;
    }

    public function disableAssignment($userId, $branchId)
    {
        $userBranch = UserBranch::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->first();

        if ($userBranch) {
            $userBranch->status = 0;
            $userBranch->effective_to = now();
            $userBranch->save();

            return $userBranch;
        }

        return false;
    }

    public function makePrimary($userId, $branchId)
    {
        DB::beginTransaction();
        try {
            $currentPrimary = UserBranch::where('user_id', $userId)
                ->where('is_primary', 1)
                ->where('status', 1)
                ->first();

            $newPrimary = UserBranch::where('user_id', $userId)
                ->where('branch_id', $branchId)
                ->where('status', 1)
                ->first();

            $currentPrimary->is_primary = 0;
            $currentPrimary->save();
            $newPrimary->is_primary = 1;
            $newPrimary->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::warning('Failed to make primary branch for user '.$userId.' and branch '.$branchId.' - Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Validates branch assignment for a given quote
     *
     * @param  mixed  $quote  The quote object to validate
     * @param  QuoteTypeId  $quoteTypeId  The type of quote
     * @return bool Returns false if validation fails, true otherwise
     */
    public function hasBranchAssignment($quote, $quoteTypeId): bool
    {
        if ($quoteTypeId == QuoteTypeId::Device) {
            return true;
        }

        $advisorPrimaryBranch = $quote->advisor?->primaryBranch() ?? null;
        $emirateOfYourVisaId = $quote->emirate_of_your_visa_id ?? null;
        $emirateOfRegistrationId = $quote->emirate_of_registration_id ?? null;
        $hasBranch = $advisorPrimaryBranch !== null && ($quoteTypeId == QuoteTypeId::Health ? $emirateOfYourVisaId !== null : $emirateOfRegistrationId !== null);

        LoggerService::info('Branch assignment validation for quote: '.$quote->code.' on sage booking', extra: [
            'advisorPrimaryBranch' => $advisorPrimaryBranch,
            'emirateOfYourVisaId' => $emirateOfYourVisaId,
            'emirateOfRegistrationId' => $emirateOfRegistrationId,
            'hasBranch' => $hasBranch,
            'quoteTypeId' => $quoteTypeId,
            'quoteUuid' => $quote->uuid,
        ]);

        if ($quoteTypeId == QuoteTypeId::Health) {
            $hasBranch = $quote->advisor?->primaryBranch()->exists() && $quote->emirate_of_your_visa_id !== null;
        } elseif ($quoteTypeId == QuoteTypeId::GroupMedical) {
            $hasBranch = $quote->advisor?->primaryBranch()->exists() && $quote->emirate_of_registration_id !== null;
        } else {
            $hasBranch = $quote->advisor?->primaryBranch()->exists();
        }

        if (! $hasBranch) {
            LoggerService::warning('Branch missing for quote: '.$quote->code);

            return false;
        }

        return true;
    }

    public function createAssignment($user, $data)
    {
        $assignment = $user->userBranches()->create($data);
        if (! $assignment) {
            return false;
        }

        $assignment->assignment_id = 'BA'.str_pad($assignment->id, 3, '0', STR_PAD_LEFT);
        $assignment->save();

        return $assignment;
    }

    public function getAssignedUsers()
    {
        $assignments = UserBranch::select('user_id')
            ->where('status', 1)
            ->groupBy('user_id')
            ->pluck('user_id')
            ->toArray();

        return $assignments;
    }

    /**
     * Retrieves the display name of a branch using provided identifiers.
     *
     * This method resolves the branch instance based on the given advisor's primary branch ID,
     * the quote type, and optionally the visa emirate ID (for Health quotes), then returns
     * its human-readable name. If no branch is found, it returns an empty string.
     *
     * @param  int|null  $primaryAdvisorBranchId  The advisor's primary branch ID
     * @param  int  $quoteTypeId  The QuoteTypeId value
     * @param  int|null  $emirateOfYourVisaId  (Optional) Visa emirate ID, used for Health quotes
     * @return string The branch's display name, or empty string if not found
     */
    public function getBranchName($primaryAdvisorBranchId, $quoteTypeId, $emirateOfYourVisaId = null): string
    {
        $branch = $this->getBranch($primaryAdvisorBranchId, $quoteTypeId, $emirateOfYourVisaId);

        return $branch->name ?? '';
    }

    /**
     * Returns the resolved branch instance for the given advisor and quote type,
     * applying Health-specific logic based on the emirate if necessary,
     * or branch override configuration for other quote types.
     *
     * @param  int|null  $primaryAdvisorBranchId  The advisor's primary branch ID
     * @param  int  $quoteTypeId  The QuoteTypeId value
     * @param  int|null  $emirateOfYourVisaId  (Optional) Visa emirate ID, required only for Health quotes
     * @return mixed|null The resolved branch model instance, or null if not found
     */
    public function getBranch($primaryAdvisorBranchId, $quoteTypeId, $emirateOfYourVisaId = null)
    {
        if ($quoteTypeId == QuoteTypeId::Device) {
            return self::$branches->find(BranchEnum::DUBAI->value);
        }

        if (in_array($quoteTypeId, [QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
            return $this->getHealthOrGroupMedicalBranch($primaryAdvisorBranchId, $emirateOfYourVisaId, $quoteTypeId);
        }

        return $this->getBranchWithOverride($primaryAdvisorBranchId, $quoteTypeId);
    }

    /**
     * Get branch for Health quotes based on emirate and advisor branch.
     *
     * @param  int|null  $primaryAdvisorBranchId
     * @param  int|null  $emirateOfYourVisaId
     */
    private function getHealthOrGroupMedicalBranch($primaryAdvisorBranchId, $emirateOfYourVisaId, $quoteTypeId)
    {
        if (empty($emirateOfYourVisaId)) {
            return null;
        }

        if (empty($primaryAdvisorBranchId)) {
            return null;
        }

        // Check if emirate is Abu Dhabi
        if ($emirateOfYourVisaId == EmirateEnum::ABU_DHABI) {
            $primaryAdvisorBranchId = BranchEnum::ABU_DHABI->value;
        }

        $branch = self::$branches->find($primaryAdvisorBranchId);

        return $branch;
    }

    /**
     * Get branch with override configuration applied.
     *
     * @param  int|null  $primaryAdvisorBranchId
     * @param  int  $quoteTypeId
     */
    private function getBranchWithOverride($primaryAdvisorBranchId, $quoteTypeId)
    {
        if (empty($primaryAdvisorBranchId)) {
            return null;
        }

        // Check if there's an active override configuration for this branch and quote type
        $overrideConfig = $this->getBranchOverrideConfig($primaryAdvisorBranchId, $quoteTypeId);
        $targetBranchId = $overrideConfig?->target_branch_id ?? $primaryAdvisorBranchId;

        $branch = self::$branches->find($targetBranchId);

        return $branch;
    }

    private function getBranchOverrideConfig($primaryAdvisorBranchId, $quoteTypeId)
    {
        return self::$branchOverrideConfigs
            ->where('source_branch_id', $primaryAdvisorBranchId)
            ->where('quote_type_id', $quoteTypeId)
            ->first() ?? null;
    }

    public function saveBranchOverride($quote, $quoteTypeId)
    {
        if (in_array($quoteTypeId, [QuoteTypeId::Health, QuoteTypeId::GroupMedical])) {
            return;
        }

        $primaryAdvisorBranchId = $quote?->advisor?->primaryBranch?->branch_id;
        $requestType = $quote::class;
        $requestId = $quote->id;
        $overrideConfig = $this->getBranchOverrideConfig($primaryAdvisorBranchId, $quoteTypeId);
        if ($overrideConfig) {
            BranchOverride::create([
                'branch_override_config_id' => $overrideConfig->id,
                'quote_request_type' => $requestType,
                'quote_request_id' => $requestId,
            ]);
        }
    }
}
