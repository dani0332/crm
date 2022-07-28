<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryBand extends Model
{
    use HasFactory;

    protected $table = 'salary_band';

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }
}
