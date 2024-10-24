<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class PolicyIssuanceLog extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $table = 'policy_issuance_logs';
    protected $fillable = [
        'policy_issuance_id',
        'model_type',
        'model_id',
        'step',
        'payload',
        'response',
        'created_at',
        'updated_at',
    ];
}
