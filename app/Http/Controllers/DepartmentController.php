<?php

namespace App\Http\Controllers;

use App\Services\DepartmentService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    private $departmentService;
    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }
    public function index()
    {
        $departments = $this->departmentService->getGridData();

        return inertia('Admin/Department/Index', compact(['departments']));
    }

    public function create()
    {
        $teams = $this->departmentService->getTeamList();

        return inertia('Admin/Department/Form', compact(['teams']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:departments,name',
            'teams' => 'required|array',
            'is_active' => 'required|boolean',
        ]);

        $this->departmentService->saveDepartment((object) $request->all());

        return redirect()->route('departments.index')->with('success', 'Department created successfully');
    }

    public function show($id)
    {
        $department = $this->departmentService->getDepartment($id);
        $teams = $this->departmentService->getTeamList();

        return inertia('Admin/Department/Show', compact(['department', 'teams']));
    }
    public function edit($id)
    {
        $department = $this->departmentService->getDepartment($id);
        $teams = $this->departmentService->getTeamList();

        return inertia('Admin/Department/Form', compact(['department', 'teams']));
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:departments,name,'.$id,
            'teams' => 'required|array',
            'is_active' => 'required|boolean',
        ]);

        $this->departmentService->updateDepartment((object) $request->all(), $id);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully');
    }

    public function destroy($id)
    {
        $this->departmentService->deleteDepartment($id);

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully');
    }
}
