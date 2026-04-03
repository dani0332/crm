<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthNationalityGroup extends Model
{
    public function countries(): HasMany
    {
        return $this->hasMany(HealthGroupNationality::class);
    }
}
