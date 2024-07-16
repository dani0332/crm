<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Department;
use App\Models\DepartmentTeams;

class DepartmentService extends BaseService
{


    public function getGridData($request=null){
        $departments = Department::latest();
        if(!empty(request('name'))){
            $departments = $departments->where('name', 'like', '%'.request('name').'%');
        }
        if(!empty(request('item_ids'))){
            $departments = $departments->whereIn('id', request('item_ids'));
        }

        return $departments->paginate();

    }

    public function saveDepartment($request){
        $new_department = Department::create([
            'name' => $request['name'],
            'status' => $request['status'],
        ]);
        foreach ($request['teams'] as $key => $team_id) {
            $teams = DepartmentTeams::create([
                'department_id' => $new_department->id,
                'team_id' => $team_id,
            ]);
        }
        return $new_department;
    }

    public function updateDepartment($request,$id){
        $department = Department::find($id);
        $department->update([
            'name' => $request['name'],
            'status' => $request['status'],
        ]);
        $department->departmentTeams()->delete();
        foreach ($request['teams'] as $key => $team_id) {
            $teams = DepartmentTeams::create([
                'department_id' => $department->id,
                'team_id' => $team_id,
            ]);
        }
        return $department;
    }
    public function deleteDepartment($id){
        $department = Department::find($id);
        $department->delete();
        return true;
    }

    public function getDepartment($id){
        return Department::find($id)->with('departmentTeams')->first();
    }

    public function getTeamList(){
        $teams = Team::all();

        return $teams;
    }

}
