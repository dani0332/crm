<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalStatusProcess extends Model
{
    use HasFactory;
    protected $fillable = ['batch', 'total_leads', 'total_completed', 'total_failed', 'status', 'created_by_id'];

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }
}
