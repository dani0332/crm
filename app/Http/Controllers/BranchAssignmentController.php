<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\StoreBranchAssignmentRequest;
use App\Models\User;
use App\Services\BranchAssignmentService;
use App\Services\BranchService;
use App\Services\UserService;
use Illuminate\Http\Request;

class BranchAssignmentController extends Controller
{
    private $branchService;
    private $userService;
    private $branchAssignmentService;
    public function __construct(BranchAssignmentService $branchAssignmentService, BranchService $branchService, UserService $userService)
    {
        $this->middleware('permission:'.PermissionsEnum::BRANCH_ASSIGNMENTS);

        $this->branchAssignmentService = $branchAssignmentService;
        $this->branchService = $branchService;
        $this->userService = $userService;
    }
    public function index(Request $request)
    {
        $advisorList = $this->userService->getAdvisors();
        $branches = $this->branchService->getBranches();
        $branchAssignments = $this->branchAssignmentService->getGridData($request->all());
        $assignedUsers = $this->branchAssignmentService->getAssignedUsers();

        return inertia('Admin/BranchAssignment/Index', compact(['branchAssignments', 'branches', 'advisorList', 'assignedUsers']));
    }

    public function store(StoreBranchAssignmentRequest $request, User $user)
    {
        $assignment = $this->branchAssignmentService->createAssignment($user, $request->validated());
        if (! $assignment) {
            return redirect()->back()->with('error', 'Failed to add branch assignment.');
        }

        return redirect()->back()->with('success', 'Branch added successfully');
    }

    public function show(User $user)
    {
        $user->load('userBranches', 'userBranches.branch');

        return inertia('Admin/BranchAssignment/Show', compact('user'));
    }

    public function disableAssignment(Request $request, User $user, $branchId)
    {
        $branches = $user->userBranches->where('status', 1);
        if ($branches->count() == 0) {
            return redirect()->back()->with('error', 'No branch assigned to this user.');
        }

        $primaryBranch = $branches->where('is_primary', 1)->first();
        if ($primaryBranch?->branch_id == $branchId) {
            return redirect()->back()->with('error', 'Cannot remove the primary branch. Please assign another active branch and set it as Primary before removal.');
        }

        $userBranch = $this->branchAssignmentService->disableAssignment($user->id, $branchId);
        if (! $userBranch) {
            return redirect()->back()->with('error', 'Invalid Branch assignment.');
        }

        return redirect()->back()->with('success', 'Branch deleted successfully');
    }

    public function makePrimary(Request $request, $userId, $branchId)
    {
        $result = $this->branchAssignmentService->makePrimary($userId, $branchId);

        if (! $result) {
            return redirect()->back()->with('error', 'Failed to update primary branch. Please ensure the branch assignment exists and is active.');
        }

        return redirect()->back()->with('success', 'Primary Branch updated successfully');
    }
}
