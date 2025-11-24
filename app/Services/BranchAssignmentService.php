<?php

namespace App\Services;

use App\Models\UserBranch;
use App\Services\Logger\LoggerService;
use Illuminate\Support\Facades\DB;
use App\Enums\QuoteTypeId;
use App\Models\Branch;
use App\Models\BranchOverrideConfig;
use App\Enums\EmirateEnum;
use App\Enums\BranchEnum;

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
        if(empty(self::$branches)) {
            self::$branches = Branch::all();
        }

        if(empty(self::$branchOverrideConfigs)) {
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
            $query->whereHas('branch', function ($query) use ($request) {
                $query->where('id', $request['primary_branch'])
                    ->where('is_primary', 1);
            });
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
        } catch (\Exception $e) {
            DB::rollBack();
            LoggerService::warning('Failed to make primary branch for user '.$userId.' and branch '.$branchId.' - Error: '.$e->getMessage());
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
        $hasBranch = $quoteTypeId == QuoteTypeId::Health
            ? ($quote->advisor?->primaryBranch()->exists() || $quote->emirate_of_your_visa_id !== null)
            : $quote->advisor?->primaryBranch()->exists();

        if (!$hasBranch) {
            LoggerService::warning('Branch missing for quote: ' . $quote->code);
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

        $assignment->assignment_id = "BA" . str_pad($assignment->id, 3, '0', STR_PAD_LEFT);
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
     * Get the branch name after applying any overrides for the quote type.
     *
     * @param int|null $primaryAdvisorBranchId
     * @param int $quoteTypeId
     * @param int|null $emirateOfYourVisaId
     * @return string
     */
    public function getBranchName($primaryAdvisorBranchId, $quoteTypeId, $emirateOfYourVisaId = null): string
    {
        if ($quoteTypeId == QuoteTypeId::Health) {
            return $this->getHealthBranchName($primaryAdvisorBranchId, $emirateOfYourVisaId);
        }

        return $this->getBranchNameWithOverride($primaryAdvisorBranchId, $quoteTypeId);
    }

    /**
     * Get branch name for Health quotes based on emirate and advisor branch.
     *
     * @param int|null $primaryAdvisorBranchId
     * @param int|null $emirateOfYourVisaId
     * @return string
     */
    private function getHealthBranchName($primaryAdvisorBranchId, $emirateOfYourVisaId): string
    {
        if (empty($emirateOfYourVisaId) || empty($primaryAdvisorBranchId)) {
            return '';
        }

        // Check if emirate is Abu Dhabi
        if ($emirateOfYourVisaId == EmirateEnum::ABU_DHABI) {
            return BranchEnum::ABU_DHABI->name();
        }

        $branch = self::$branches->find($primaryAdvisorBranchId);
        
        return $branch->name ?? '';
    }

    /**
     * Get branch name with override configuration applied.
     *
     * @param int|null $primaryAdvisorBranchId
     * @param int $quoteTypeId
     * @return string
     */
    private function getBranchNameWithOverride($primaryAdvisorBranchId, $quoteTypeId): string
    {
        if (empty($primaryAdvisorBranchId)) {
            return '';
        }

        // Check if there's an active override configuration for this branch and quote type
        $overrideConfig = self::$branchOverrideConfigs
            ->where('source_branch_id', $primaryAdvisorBranchId)
            ->where('quote_type_id', $quoteTypeId)
            ->first();

        $targetBranchId = $overrideConfig?->target_branch_id ?? $primaryAdvisorBranchId;
        $branch = self::$branches->find($targetBranchId);

        return $branch?->name ?? '';
    }


}
