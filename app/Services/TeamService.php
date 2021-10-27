<?php

namespace App\Services;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamService extends BaseService
{

	public static function saveTeam(Request $request)
	{
        $team = new Team();
        $team->name = $request->name;
        $team->save();
	}

    public static function updateTeam(Request $request, $id)
	{
        $team = Team::find($id);
        $team->name = $request->name;
        $team->save();

        if (isset($request->return_to_view))
            return redirect("quote/teams/" . $team->id)->with('success', 'Team has been updated');
	}

    public static function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "name" => "input|text|required|title",
            "team_users" => "select|multiple|title|required",
        );
    }

    public static function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'name':
                $title = "Team Name";
                break;
            case 'team_users':
                $title = 'Team Users';
            default:
                break;
        }
        return $title;
    }

    public static function fillModelSkipProperties() {
        return [];
    }
}
