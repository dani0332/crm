<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleUser extends Model
{
    use HasFactory;

    /**
     * attributes those are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'rule_id',
        'user_id',
        'created_at',
        'updated_at',
    ];

    /**
     * get rule users details function
     */
    public function users(): HasMany
    {
        return $this->hasMany(
            User::class,
            'user_id',
            'id'
        );
    }
}
