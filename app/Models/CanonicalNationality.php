<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanonicalNationality extends Model
{
    use HasFactory;
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }
}
