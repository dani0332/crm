<?php

namespace App\Services\Life;

use App\Models\LifeChildren;
use App\Services\BaseService;

class LifeChildrenService extends BaseService
{
    public function getActive()
    {
        return LifeChildren::withActive()->select('id', 'text')->get();
    }
}
