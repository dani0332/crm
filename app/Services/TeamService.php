<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Http\Request;
use DB;

class TeamService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = DB::table('teams as t')
            ->leftJoin('teams as pt', 'pt.id', '=', 't.parent_team_id')
            ->select('t.id', 't.uuid', 't.name AS name', 't.parent_team_id', 'pt.name AS parent_team_id_text');
    }

    public function getEntity($id)
    {
        return $this->query->where('t.id', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return Team::where('id', $id)->first();
    }

    public function saveTeams(Request $request)
    {
        $existingTeam = Team::where('name', $request->name)->first();
        if ($existingTeam != null) {
            return "Error: name already exists";
        }
        $team = new Team();
        $team->name = $request->name;
        if(isset($request->parent_team_id)){
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->save();
        return $team;
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else {
                        $this->query->where('t.' . $item, $request[$item]);
                    }
                }
            }
        }
        return $this->query->orderBy('t.created_at', 'DESC');
    }


    public function updateTeams(Request $request, $id)
    {
        $team = Team::where('id', $id)->first();
        $team->name = $request->name;
        if(isset($request->parent_team_id)){
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->save();
        if (isset($request->return_to_view))
            return redirect("/quotes/teams/" . $id)->with('success', 'Team has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "name" => "input|text|required|title",
            "parent_team_id" => "select|title",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'name':
                $title = "Team Name";
                break;
            case 'parent_team_id':
                $title = "Parent Team";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            'create' => '',
            'list' => '',
            'show' => '',
            'update' => '',
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["name"];
    }
}
