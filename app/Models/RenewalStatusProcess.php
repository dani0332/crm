<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalStatusProcess extends Model
{
    use HasFactory;

    protected $fillable = ['batch', 'total_leads', 'total_completed', 'total_failed', 'status', 'user_id', 'renewal_batch_id'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function personalQuotes()
    {
        return $this->hasMany(PersonalQuote::class, 'renewal_batch', 'batch');
    }

    public function renewalBatch()
    {
        return $this->belongsTo(RenewalBatch::class, 'renewal_batch_id');
    }
}
