<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthGroupNationality extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(HealthNationalityGroup::class);
    }
}
