<?php

namespace App\Models;

class GenericModel extends BaseModel
{
    public $modelType;
    public $properties = [];
    public $skipProperties = [];
    public $searchProperties = [];
    public $renewalSkipProperties = [];
    public $renewalSearchProperties = [];
    public $newBusinessSkipProperties = [];
    public $newBusinessSearchProperties = [];
    public $sortProperties = [];
}
