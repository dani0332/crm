<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
class BaseModel extends Model
{

    public function processGetBaseDSL($filters = [], $table , $select) {
        $response = DB::table($table)
            ->select($select)
            ->where(function($query) use($filters) {
                foreach($filters as $key => $value) {
                    $query->where($key,$value);
                }
            })
            ->get();
        return $response;
    }
}
