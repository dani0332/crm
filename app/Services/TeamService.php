<?php

namespace App\Services;

use App\Models\Team;
use DB;
use Illuminate\Http\Request;

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
            return 'Error: name already exists';
        }
        $team = new Team();
        $team->name = $request->name;
        if (isset($request->parent_team_id)) {
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->save();

        return $team;
    }

    public function getGridData($model, $request)
    {
        $this->query = addSearchClauses($model, $request, $this->query, 't.');
        $this->query = addOrderByClauses($request, $this->query, 't.');

        return $this->query;
    }

    public function updateTeams(Request $request, $id)
    {
        $team = Team::where('id', $id)->first();
        $team->name = $request->name;
        if (isset($request->parent_team_id)) {
            $team->parent_team_id = $request->parent_team_id;
        }
        $team->save();
        if (isset($request->return_to_view)) {
            return redirect('/quotes/teams/'.$id)->with('success', 'Team has been updated');
        }
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'name' => 'input|text|required|title',
            'parent_team_id' => 'select|title',
        ];
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = '';
        switch ($propertyName) {
            case 'name':
                $title = 'Team Name';
                break;
            case 'parent_team_id':
                $title = 'Parent Team';
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
        return ['name'];
    }
}
