<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalQuoteProcess extends Model
{
    use HasFactory;

    protected $fillable = ['quote_type', 'policy_number', 'data', 'batch', 'validation_errors', 'status', 'email_sent', 'type'];
    protected $casts = [
        'data' => 'array',
        'validation_errors' => 'array',
    ];

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    public function setValidationErrorsAttribute($value)
    {
        $this->attributes['validation_errors'] = json_encode($value);
    }
}
