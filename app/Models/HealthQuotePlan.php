<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthQuotePlan extends Model
{
    use HasFactory;

    protected $table = 'health_quote_plans';
    protected $guarded = ['id'];

    public function payload(): Attribute
    {
        return Attribute::make(
            get: function () {
                return json_decode($this->plan_payload);
            },
        );
    }
}
