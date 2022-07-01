<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthMemberDetail extends Model
{
    use HasFactory;
    protected $table = 'health_quote_request_member_details';
    protected $guarded = ['id'];

    public function healthQuote()
    {
        return $this->belongsTo(HealthQuote::class, 'id', 'primary_member_id'); 
    }
}
