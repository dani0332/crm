<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCountryGroup extends Model
{
    public function countries(): HasMany
    {
        return $this->hasMany(HealthGroupCountry::class);
    }
}
