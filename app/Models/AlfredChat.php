<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AlfredChat extends Model
{
    protected $connection = 'alfred-chat-mongo';
    protected $collection = 'chats';
    protected $casts = ['createdAt' => 'datetime', 'updatedAt' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
