<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyActivityType extends Model
{
    protected $table = 'company_activity_type';

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
