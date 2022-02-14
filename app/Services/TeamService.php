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
            ->select('t.id', 't.uuid', 't.name AS name');
    }

    public function getEntity($id)
    {
        return $this->query->where('t.uuid', $id)->first();
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
        $team->save();
        return Team::find($team->id)->uuid;
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
        Team::where('uuid', $id)->update(
            ['name' => $request->name]
        );
        if (isset($request->return_to_view))
            return redirect("/quotes/teams/" . $id)->with('success', 'Team has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "name" => "input|text|required|title",
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'name':
                $title = "Team Name";
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
