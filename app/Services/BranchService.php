<?php

namespace App\Services;

use App\Models\Branch;

class BranchService extends BaseService
{
    public function getGridData($request)
    {
        $branches = Branch::latest()
            ->when(! empty($request['name']), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request['name'].'%');
            });

        return $branches->paginate();
    }

    public function saveBranch($data)
    {
        $branch = Branch::create($data);

        return $branch;
    }

    public function updateBranch($data, Branch $branch)
    {
        $branch->update($data);

        return $branch;
    }

    public function getBranches()
    {
        return Branch::active()->get();
    }
}
