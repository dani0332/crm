<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiderOption extends Model
{
    protected $table = 'rider_option';
    protected $guarded = [];
    protected $casts = [
        'input_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(InsuranceProviderPlan::class, 'plan_id');
    }

    public function rider()
    {
        return $this->belongsTo(Rider::class, 'rider_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
