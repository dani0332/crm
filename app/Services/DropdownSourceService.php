<?php

namespace App\Services;

use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\Emirate;
use App\Models\HealthCoverFor;
use App\Models\LeadStatus;
use App\Models\MartialStatus;
use App\Models\Nationality;
use App\Models\Regions;
use App\Models\TravelCoverFor;
use App\Models\User;
use DB;

class DropdownSourceService extends BaseService
{

    public function getCustomDropdownList($type, $id){
        $data = '';
        switch ($type) {
            case 'team_managers':
                $data = DB::select("select u.id, u.name from users u
                inner join team_managers tm on tm.manager_id = u.id
                where tm.team_id =".$id);
                break;
            case 'team_users':
                $data = DB::select("select u.id, u.name from users u
                inner join user_team ut on ut.user_id = u.id
                where ut.team_id =".$id);
                break;
            default:
                break;
        }
        return $data;
    }

    public function getOnlySelectedItemName($type, $id){
        $data = '';
        switch ($type) {
            case 'team_managers':
                $data = DB::select("select group_concat(u.name) as names from users u
                inner join team_managers tm on tm.manager_id = u.id
                where tm.team_id =".$id);
                break;
            case 'team_users':
                $data = DB::select("select group_concat(u.name) as names from users u
                inner join user_team ut on ut.user_id = u.id
                where ut.team_id =".$id);
                break;
            default:
                break;
        }
        return $data;
    }

	public function getDropdownSource($type)
	{
        $data = '';
        switch ($type) {
            case 'marital_status_id':
                $data = MartialStatus::select('id', 'text')->get();
                break;
            case 'nationality_id':
                $data = Nationality::select('id', 'text')->get();
                break;
            case 'lead_status':
                $data = LeadStatus::select('id, text')->get();
                break;
            case 'cover_for_id':
                $data = HealthCoverFor::select('id','text')->get();
                break;
            case 'emirate_of_your_visa_id':
                $data = Emirate::select('id','text')->get();
                break;
            case 'team_users':
                $data = User::select('id','name')->get();
                break;
            case 'car_make_id':
                $data = CarMake::select('id','text')->get();
                break;
            case 'car_model_id':
                $data = [];
                break;
            case 'region_cover_for_id':
                $data = Regions::select('id','text')->get();
                break;
            case 'travel_cover_for_id':
                $data = TravelCoverFor::select('id','text')->get();
                break;
            case 'team_managers':
                $data = User::select('users.id','users.name')
                ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', '=','Manager')->get();
                break;
            default:
                break;
        }
        return $data;
	}

    public function getCarModel($makeCode){
        return CarModel::select('id','text')->where('code', $makeCode)->get();
    }
}
