<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DttRevival extends Model
{
    use HasFactory;

    protected $table = 'dtt_revivals';
    protected $fillable = ['quote_type_id', 'quote_id', 'uuid', 'email_sent', 'reply_received', 'is_assigned', 'revival_quote_batch_id', 'previous_health_plan_type', 'is_active', 'created_at', 'updated_at'];
}
