<?php

namespace App\Models;

use App\Models\BaseModel;

class GenericModel extends BaseModel
{
    public $modelType;
    public $properties = [];
    public $skipProperties = [];
}
