<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyIssuanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_issuance_id',
        'model_type',
        'model_id',
        'step',
        'endPoint',
        'payload',
        'response',
        'status',
        'created_at',
        'updated_at',
    ];
}
