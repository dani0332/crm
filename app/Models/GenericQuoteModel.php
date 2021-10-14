<?php

namespace App\Models;

use App\Models\BaseModel;

class GenericQuoteModel extends BaseModel
{
    public $quoteType;
    public $properties = [];
    public $skipProperties = [];
}
