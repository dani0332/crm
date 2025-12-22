<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Emirate extends BaseModel
{
    use HasFactory;

    protected $table = 'emirates';

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    public static function getActiveEmirates()
    {
        return Cache::remember('active_emirates', now()->addHours(24), function () {
            return self::where('is_active', 1)->select('id', 'text')->get();
        });
    }
}
