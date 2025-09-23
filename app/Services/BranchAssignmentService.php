<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Models\UserBranch;

class BranchAssignmentService extends BaseService
{
    public function getGridData($request)
    {
        $dataset = User::select('id', 'name')
            // ->whereHas('usersroles', function ($query) {
            //     $query->where('name', 'like', '%advisor%');
            // })
            ->with('userBranches', 'userBranches.branch')
            ->when(! empty($request['advisors']), function ($query) use ($request) {
                $query->whereIn('id', $request['advisors']);
            })
            ->when(! empty($request['primary_branch']), function ($query) use ($request) {
                $query->whereHas('userBranches', function ($query) use ($request) {
                    $query->where('branch_id', $request['primary_branch'])
                        ->where('is_primary', 1);
                });
            })
            ->paginate();

        $dataset->map(function ($item) {

            $item->current_branches = $item->userBranches
                ->where('status', 1)
                ->pluck('branch.name')
                ->implode(', ');
            $primaryBranch = $item->userBranches
                ->where('status', 1)
                ->where('is_primary', 1)
                ->first();
            $item->primary_branch = $primaryBranch?->branch->name;
            $item->effective_from = $primaryBranch?->effective_from;
            $item->effective_to = $primaryBranch?->effective_to;

            return $item;
        });

        return $dataset;
    }

    public function getDetails($id)
    {
        $dataset = User::select('id', 'name')
            ->with('userBranches', 'userBranches.branch')
            ->where('id', $id)
            ->first();

        return [
            'user' => $dataset,
            'activeUserBranches' => $dataset->userBranches->where('status', 1)->values(),
            'historicalUserBranches' => $dataset->userBranches->where('status', 0)->values(),
        ];
    }

    public function saveBranch($data, $userId)
    {
        UserBranch::create([
            'user_id' => $userId,
            ...$data,
        ]);
    }

    public function updateBranch($data, $id)
    {
        $branch = Branch::where('id', $id)->first();
        if (empty($branch)) {
            return false;
        }
        $branch->update($data);

        return $branch;
    }

    public function getBranches($userId)
    {
        return UserBranch::where('user_id', $userId)->where('status', 1)->get();
    }

    public function getBranch($id)
    {
        return Branch::where('id', $id)->first();
    }

    public function hasBranches($userId)
    {
        return UserBranch::where('user_id', $userId)->where('status', 1)->count();
    }

    public function disableAssignment($userId, $branchId)
    {
        $userBranch = UserBranch::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->first();
        $userBranch->status = 0;
        $userBranch->effective_to = now();
        $userBranch->save();
    }

    public function makePrimary($userId, $branchId)
    {
        $userBranch = UserBranch::where('user_id', $userId)
            ->where('is_primary', 1)
            ->where('status', 1)
            ->first();
        $userBranch->is_primary = 0;
        $userBranch->save();

        $userBranch = UserBranch::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->first();
        $userBranch->is_primary = 1;
        $userBranch->save();
    }
}
