<?php

namespace App\Models;

use App\Enums\GenericRequestEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalStatusProcess extends Model
{
    use HasFactory;

    protected $fillable = ['batch', 'total_leads', 'total_completed', 'total_failed', 'status', 'user_id', 'skip_plans'];
    protected $appends = ['skip_plans_label'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getSkipPlansLabelAttribute($value)
    {
        return ($value) ? GenericRequestEnum::Yes : GenericRequestEnum::No;
    }
}
