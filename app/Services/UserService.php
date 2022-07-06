<?php

namespace App\Services;

use App\Models\User;
use DB;

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
}
