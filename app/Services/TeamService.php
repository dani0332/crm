<?php

namespace App\Services;
use App\Models\Team;
use App\Models\TeamManagers;
use App\Models\User;
use App\Models\UserTeams;
use Illuminate\Http\Request;
use DB;

class TeamService extends BaseService
{

	public function saveTeam(Request $request)
	{
        $team = new Team();
        $team->name = $request->name;
        $team->save();
        dd($request->team_users);
        foreach ($request->team_users as $team_user) {
            $userTeam = new UserTeams();
            $userTeam->team_id = $team->id;
            $userTeam->user_id = $team_user;
            $userTeam->save();
        }

        foreach ($request->team_managers as $team_manager) {
            $teamManager = new TeamManagers();
            $teamManager->team_id = $team->id;
            $teamManager->manager_id = $team_manager;
            $teamManager->save();
        }

	}

    public function getGridData(){
        return DB::select("
                        SELECT t.id, t.name AS name
                        ,group_concat(u.name) AS team_users
                    FROM teams t
                    LEFT JOIN user_team ut ON ut.team_id = t.id
                    LEFT JOIN users u ON u.id = ut.user_id
                    GROUP BY t.name
                        ,t.id
                ");
    }

    public function updateTeam(Request $request, $id)
	{
        $team = Team::find($id);
        $team->name = $request->name;
        $team->save();

        UserTeams::where('team_id', '=', $id)->delete();
        TeamManagers::where('team_id', '=', $id)->delete();
        foreach ($request->team_users as $team_user) {
            $user = User::find($team_user);
            $userTeam = new UserTeams();
            $userTeam->team_id = $team->id;
            $userTeam->user_id = $user->id;
            $userTeam->save();
        }

        foreach ($request->team_managers as $team_manager) {
            $user = User::find($team_manager);
            $teamManager = new TeamManagers();
            $teamManager->team_id = $team->id;
            $teamManager->manager_id = $user->id;
            $teamManager->save();
        }
        if (isset($request->return_to_view))
            return redirect("quote/teams/" . $team->id)->with('success', 'Team has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "name" => "input|text|required|title",
            "team_users" => "select|multiple|title|required|customTable",
            "team_managers" => "select|multiple|title|required|customTable",
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'name':
                $title = "Team Name";
                break;
            case 'team_users':
                $title = 'Team Users';
                break;
            case 'team_managers':
                $title = 'Team Managers ';
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            'create' => '',
            'list' => 'team_managers',
        ];
    }
}
