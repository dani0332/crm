<?php

namespace App\helpers;

use Exception;

class LookUpModelHelper
{
    public static function getLookModel($model, $query)
    {
        $Model = '\\App\\Models\\'.$model;
        $result = $Model::where([$query])->first();
        if (! $result) {
            throw new Exception('Not found');
        }

        return $result->id;
    }
}
