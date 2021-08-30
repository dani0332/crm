<?php

namespace App\Services;
use DB;

class UserService extends BaseService
{
	public static function getRolesByUserId($userId)
	{
		return DB::select('select * from model_has_roles where model_id = ?', [$userId])->get();
	}
}
