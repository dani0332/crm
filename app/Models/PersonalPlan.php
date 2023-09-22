<?php

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalPlan extends Model
{
    use FilterCriteria, HasFactory;

    public $filterables = [
        'insurance_provider_id' => FilterTypes::EXACT,
    ];
}
