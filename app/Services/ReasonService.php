<?php

namespace App\Services;

use App\Models\Reason;

class ReasonService extends BaseService
{
    public static function getReasonById($id)
    {
        return Reason::where('id', '=', $id)->get();
    }
}
