<?php

namespace App\Services;
use App\Models\Team;
use App\Models\User;
use App\Models\UserTeams;
use Illuminate\Http\Request;
use DB;

class TeamService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = "SELECT t.id, t.name AS name
                        ,group_concat(u.name) AS team_users
                        ,group_concat(u.name) AS team_users_text
                        FROM teams t
                        LEFT JOIN user_team ut ON ut.team_id = t.id
                        LEFT JOIN users u ON u.id = ut.user_id ";
    }

    public function getEntity($id){
        return DB::select($this->query.' where t.id = '. $id.' GROUP BY t.name,t.id');
    }

    public function getEntityPlain($id){
        return Team::find($id);
    }

	public function saveTeams(Request $request)
	{
        $team = new Team();
        $team->name = $request->name;
        $team->save();
        foreach ($request->team_users as $team_user) {
            $user = User::find($team_user);
            $userTeam = new UserTeams();
            $userTeam->team_id = $team->id;
            $userTeam->user_id = $user->id;
            $userTeam->save();
        }

        if (isset($request->return_to_view))
            return redirect("/quotes/teams/" . $team->id)->with('success', 'Team has been created');

	}

    public function getGridData($searchProperties, $request){

        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if(!empty($request[$item])){
                    $suffix = 't';
                    $this->query = $this->query. $count > 0 ? ' and' : ' where '.$suffix.'.'.$item.'='."'".$request[$item]."'";

                    $count++;
                }
            }
        }
        $this->query = $this->query .' GROUP BY t.name,t.id';
        return DB::select($this->query);
    }


    public function updateTeams(Request $request, $id)
	{
        $team = Team::find($id);
        $team->name = $request->name;
        $team->save();

        UserTeams::where('team_id', '=', $id)->delete();
        foreach ($request->team_users as $team_user) {
            $user = User::find($team_user);
            $userTeam = new UserTeams();
            $userTeam->team_id = $team->id;
            $userTeam->user_id = $user->id;
            $userTeam->save();
        }

        if (isset($request->return_to_view))
            return redirect("/quotes/teams/" . $team->id)->with('success', 'Team has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "name" => "input|text|required|title",
            "team_users" => "select|multiple|title|required|customTable",
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
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            'create' => '',
            'list' => '',
        ];
    }

    public function fillModelSearchProperties(){
        return ["name"];
    }
}
