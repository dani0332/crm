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
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile_no = $request->mobile_no;
        $user->landline_no = $request->landline_no;
        $user->calendar_link = $request->calendar_link;
        $user->phone_calendar_link = $request->phone_calendar_link;
        $user->department_id = $request->department_id ?? null;
        $user->password = bcrypt($request->password);
        $user->is_active = true;
        if ((! empty($request->additionalTeams) && $request->sub_team_id != '0')) {
            $user->sub_team_id = $request->sub_team_id;
        }
        if (! empty($request->additionalTeams) && isset($request->additionalTeams)) {
            if (count((array) $request->additionalTeams) > 0) {
                $user->additional_team_ids = implode(',', $request->additionalTeams);
            } else {
                $user->additional_team_ids = $request->additionalTeams[0];
            }
        }

        $user->save();

        if ($request->department_ids != null) {
            app(DepartmentService::class)->syncUserDepartments($user, $request->department_ids);
        }

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

        // also add permissions for user
        if (isset($request->permissions)) {
            DB::table('model_has_permissions')->where('model_id', $user->id)->delete();
            foreach ($request->permissions as $permissionId) {
                DB::table('model_has_permissions')->insert([
                    'model_id' => $user->id,
                    'permission_id' => $permissionId,
                    'model_type' => 'App\Models\User',
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

    public function getDepartmentsList()
    {
        return DB::table('departments')->where('is_active', 1)->orderBy('name')->get();
    }
}
