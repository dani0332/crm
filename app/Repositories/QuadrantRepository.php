<?php

namespace App\Repositories;

use App\Models\Quadrant;
use DB;

class QuadrantRepository extends BaseRepository
{
    public function model()
    {
        return Quadrant::class;
    }

    public static function getData()
    {

        $data = self::orderBy('id');
        if (request()->name) {
            $data->where('name', 'LIKE','%'.request()->name.'%');
        }

        return $data->simplePaginate(10)->withQueryString();

    }

    public static function deleteQuad($id)
    {
        $quad = self::where('id', $id)->first();
        $quad->is_active = 0;
        $quad->save();
        DB::table('quad_tiers')->where('quad_id', $quad->id)->delete();
        DB::table('quad_users')->where('quad_id', $quad->id)->delete();
        return $quad->delete();
    }
}
