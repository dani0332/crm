<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
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
        if (isset($request->additionalTeams)) {
            if (count((array) $request->additionalTeams) > 0) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            } else {
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }
        $user->save();
        if ($request->manager != '0' && isset($request->manager)) {
            DB::table('user_manager')->where('user_id', $user->id)->delete();
            foreach ($request->manager as $managerId) {
                DB::table('user_manager')->insert([
                    'user_id' => $user->id,
                    'manager_id' => $managerId,
                ]);
            }
        }
        if ($request->teams != '0') {
            DB::table('user_team')->where('user_id', $user->id)->delete();
            foreach ($request->teams as $teamId) {
                DB::table('user_team')->insert([
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                ]);
            }
        }
        if ($request->products != '0') {
            DB::table('user_products')->where('user_id', $user->id)->delete();
            foreach ($request->products as $productId) {
                DB::table('user_products')->insert([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ]);
            }
        }

        return $user;
    }

    public function getUserById($userId)
    {
        return User::where('id', $userId)->first();
    }

    public function isAllowedToShowLeadListReport()
    {
        if (auth()->user()->hasAnyRole([RolesEnum::Admin])) {
            return true;
        } elseif (auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager])) {
            if (in_array(TeamNameEnum::ORGANIC, app(User::class)->getUserTeams(auth()->user()->id)->toArray())) {
                return true;
            }
        }

        return false;
    }
}
