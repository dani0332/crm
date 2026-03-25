<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Emirate extends BaseModel
{
    use HasFactory;

    protected $table = 'emirates';

    /**
     * Local scope: {@code Emirate::query()->withActive()}.
     */
    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }

    public static function getActiveEmirates()
    {
        return Cache::remember('active_emirates_all', now()->addHours(24), function () {
            return self::where('is_active', 1)->select('id', 'text')->orderBy('text', 'asc')->get();
        });
    }
}
