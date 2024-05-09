<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DttRevival extends Model
{
    use HasFactory;

    protected $table = 'dtt_revivals';
    protected $fillable = ['quote_type_id', 'quote_id', 'uuid', 'email_sent', 'reply_received', 'is_assigned', 'created_at', 'updated_at'];
}
