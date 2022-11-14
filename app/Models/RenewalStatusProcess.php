<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalStatusProcess extends Model
{
    use HasFactory;

    protected $fillable = ['batch', 'total_leads', 'total_completed', 'total_failed', 'status', 'user_id'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
