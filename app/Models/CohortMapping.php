<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CohortMapping extends Model
{
    protected $table = 'cohort_mapping';
    protected $fillable = [
        'visa_type',
        'member_classification',
        'is_policyholder_covered',
        'cohort',
    ];
}
