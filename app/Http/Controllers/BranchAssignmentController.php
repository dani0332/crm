<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Services\BranchAssignmentService;
use App\Services\BranchService;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;

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

        return inertia('Admin/BranchAssignment/Index', compact(['branchAssignments', 'branches', 'advisorList']));
    }

    public function create(User $user)
    {
        $branches = $this->branchService->getBranches();
        $activeBranchCount = $user->userBranches->where('status', 1)->count();

        return inertia('Admin/BranchAssignment/Form', [
            'userId' => $user->id,
            'branches' => $branches,
            'activeBranchCount' => $activeBranchCount,
        ]);
    }

    public function store(Request $request, $userId)
    {
        $validated = $request->validate([
            'branch_id' => 'required|unique:branches,name',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable',
            'is_primary' => 'nullable',
        ]);

        $branches = $this->branchAssignmentService->getUserBranches($userId);
        if ($request->input('is_primary')) {
            $existingPrimary = $branches
                ->where('is_primary', true)
                ->count();

            if ($existingPrimary) {
                return redirect()->back()->with('error', 'This user already has a primary branch.');
            }
        }

        if ($validated['branch_id']) {
            $existingAssignment = $branches
                ->where('branch_id', $validated['branch_id'])
                ->count();

            if ($existingAssignment) {
                return redirect()->back()->with('error', 'This branch is already assigned to the user.');
            }
        }

        $this->branchAssignmentService->saveUserBranch($validated, $userId);

        return redirect()->route('branch-assignments.show', $userId)->with('success', 'Branch added successfully');
    }

    public function show(User $user)
    {
        $user->load('userBranches', 'userBranches.branch');
        return inertia('Admin/BranchAssignment/Show', compact('user'));
    }

    public function disableAssignment($userId, $branchId)
    {
        $branches = $this->branchAssignmentService->getUserBranches($userId);
        if ($branches->count() == 0) {
            return redirect()->route('branch-assignments.show', $userId)->with('error', 'No branch assigned to this user.');
        }

        $primaryBranch = $branches->where('is_primary', 1)->first();
        if ($primaryBranch?->branch_id == $branchId) {
            return redirect()->route('branch-assignments.show', $userId)->with('error', 'Cannot remove the primary branch. Please assign another active branch and set it as Primary before removal.');
        }

        $userBranch = $this->branchAssignmentService->disableAssignment($userId, $branchId);
        if (!$userBranch) {
            return redirect()->route('branch-assignments.show', $userId)->with('error', 'Invalid Branch assignment.');
        }

        return redirect()->route('branch-assignments.show', $userId)->with('success', 'Branch deleted successfully');
    }

    public function makePrimary($userId, $branchId)
    {
        $this->branchAssignmentService->makePrimary($userId, $branchId);

        return redirect()->route('branch-assignments.show', $userId)->with('success', 'Primary Branch updated successfully');
    }
}
