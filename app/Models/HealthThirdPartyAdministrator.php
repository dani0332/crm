<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthThirdPartyAdministrator extends Model
{
    protected $table = 'health_third_party_administrator';

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
