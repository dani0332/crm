<?php

namespace App\Services\Life;

use App\Models\LifeChildren;
use App\Services\BaseService;

class LifeChildrenService extends BaseService
{
    public function getActive()
    {
        return LifeChildren::where('is_active', 1)->select('id', 'text')->get();
    }
}
