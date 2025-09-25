<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:branches,name',
            'code' => 'required|unique:branches,code',
            'type' => 'required|string',
            'status' => 'required|boolean',
        ]);

        $this->branchService->saveBranch($validated);

        return redirect()->route('branches.index')->with('success', 'Branch created successfully');
    }

    public function show($id)
    {
        $branch = $this->branchService->getBranch($id);
        abort_if(! $branch, 404);

        return inertia('Admin/Branch/Show', [
            'branch' => $branch,
        ]);
    }

    public function edit($id)
    {
        $branch = $this->branchService->getBranch($id);
        abort_if(! $branch, 404);

        return inertia('Admin/Branch/Form', [
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|unique:branches,name,'.$id,
            'code' => 'required|unique:branches,code,'.$id,
            'type' => 'required|string',
            'status' => 'required|boolean',
        ]);

        $branch = $this->branchService->getBranch($id);
        abort_if(! $branch, 404);

        if ($validated['status'] != $branch->status) {
            $activeUserBranches = $branch->userBranches->where('status', 1);
            if ($activeUserBranches->count() > 0) {
                return redirect()->back()->with('error', 'Branch cannot be updated because it has active users');
            }
        }

        $this->branchService->updateBranch($validated, $id);

        return redirect()->route('branches.show', $id)->with('success', 'Branch updated successfully');
    }
}
