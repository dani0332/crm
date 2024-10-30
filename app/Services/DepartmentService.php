<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentTeams;
use App\Models\Team;

class DepartmentService extends BaseService
{
    public function getGridData()
    {
        $departments = Department::latest();
        if (! empty(request('name'))) {
            $departments = $departments->where('name', 'like', '%'.request('name').'%');
        }
        if (! empty(request('item_ids'))) {
            $departments = $departments->whereIn('id', request('item_ids'));
        }

        return $departments->paginate();

    }

    public function saveDepartment($data)
    {
        $new_department = Department::create([
            'name' => $data->name,
            'is_active' => $data->is_active,
        ]);
        foreach ($data->teams as $team_id) {
            DepartmentTeams::create([
                'department_id' => $new_department->id,
                'team_id' => $team_id,
            ]);
        }

        return $new_department;
    }

    public function updateDepartment($data, $id)
    {
        $department = Department::where('id', $id)->first();
        if (empty($department)) {
            return false;
        }
        $department->name = $data->name;
        $department->is_active = $data->is_active;
        $department->save();
        $department->teams()->delete();
        foreach ($data->teams as $team_id) {
            DepartmentTeams::create([
                'department_id' => $department->id,
                'team_id' => $team_id,
            ]);
        }

        return $department;
    }
    public function deleteDepartment($id)
    {
        $department = Department::where('id', $id)->first();
        $department->delete();

        return true;
    }

    public function getDepartment($id)
    {
        return Department::where('id', $id)->with('teams')->first();
    }

    public function getTeamList()
    {
        return Team::all();
    }

    public function syncUserDepartments($user, $departmentIds)
    {
        $user->departments()->sync($departmentIds);  // Sync the department IDs
    }

}
