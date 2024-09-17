<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SageProcess extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'insurance_provider_id', 'model_type', 'model_id', 'request', 'message', 'status'];

    public function model()
    {
        return $this->morphTo();
    }
}
