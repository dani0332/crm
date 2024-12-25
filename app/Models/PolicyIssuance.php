<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyIssuance extends Model
{
    use HasFactory;

    protected $table = 'policy_issuance';
    protected $fillable = ['insurance_provider_id', 'model_type', 'model_id', 'quote_type', 'completed_step', 'message', 'status'];

    public function model()
    {
        return $this->morphTo();
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function policyIssuanceLogs()
    {
        return $this->hasMany(PolicyIssuanceLog::class);
    }

}
