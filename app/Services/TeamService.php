<?php

namespace App\Services;

use App\Enums\TeamTypeEnum;
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
            ->select('t.id', 't.uuid', 't.name AS name', 't.parent_team_id', 'pt.name AS parent_team_id_text', 't.type');
    }

    public function getEntity($id)
    {
        return $this->query->where('t.id', $id)->first();
    }

    public function getEntityPlainByName($name)
    {
        return Team::where('name', $name)->first();
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
        $team = new Team;
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
            return redirect('/quotes/team/'.$id)->with('success', 'Team has been updated');
        }
    }

    public function fillModelProperties()
    {
        return [
            'id' => 'readonly|none',
            'name' => 'input|text|required|title',
            'type' => '|static|title|'.TeamTypeEnum::PRODUCT_STR.','.TeamTypeEnum::TEAM_STR.','.TeamTypeEnum::SUBTEAM_STR.'',
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
                $title = 'Parent Record';
                break;
            case 'type':
                $title = 'Record Type';
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

    public function getTeams()
    {
        return Team::where('is_active', 1)->whereNotNull('type')->get();
    }

    public function getTeamNameById($teamId)
    {
        $team = Team::where('id', $teamId)->first();

        return $team ? $team->name : '';
    }
}
