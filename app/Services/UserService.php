<?php

namespace App\Services;

use App\Models\User;
use DB;
use Illuminate\Http\Request;

class UserService extends BaseService
{
    public static function getRolesByUserId($userId)
    {
        return DB::select('select * from model_has_roles where model_id = ?', [$userId])->get();
    }

    public function getUserNameById($id)
    {
        return User::find($id)->name;
    }

    public function createUserRecord(Request $request)
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile_no = $request->mobile_no;
        $user->landline_no = $request->landline_no;
        $user->password = bcrypt($request->password);
        $user->is_active = true;
        if ($request->sub_team_id != '0') {
            $user->sub_team_id = $request->sub_team_id;
        }

        if ($request->manager != '0') {
            $user->manager_id = $request->manager;
        } else {
            $user->manager_id = null;
        }

        if (isset($request->additionalTeams)) {
            if (count((array) $request->additionalTeams) > 0) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            } else {
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }
        $user->team_id = $request->team;
        $user->save();

        return $user;
    }
}
