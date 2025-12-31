<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    private $branchService;
    public function __construct(BranchService $branchService)
    {
        $this->middleware('permission:'.PermissionsEnum::BRANCHES);

        $this->branchService = $branchService;
    }
    public function index(Request $request)
    {
        $branches = $this->branchService->getGridData($request->all());

        return inertia('Admin/Branch/Index', compact(['branches']));
    }

    public function create()
    {
        return inertia('Admin/Branch/Form');
    }

    public function store(StoreBranchRequest $request)
    {
        $this->branchService->saveBranch($request->validated());

        return redirect()->route('branches.index')->with('success', 'Branch created successfully');
    }

    public function show(Branch $branch)
    {
        return inertia('Admin/Branch/Show', [
            'branch' => $branch,
        ]);
    }

    public function edit(Branch $branch)
    {
        return inertia('Admin/Branch/Form', [
            'branch' => $branch,
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $this->branchService->updateBranch($request->validated(), $branch);

        return redirect()->route('branches.show', $branch->id)->with('success', 'Branch updated successfully');
    }
}
