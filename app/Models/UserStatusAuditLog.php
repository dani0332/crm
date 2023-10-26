<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatusAuditLog extends Model
{
    protected $table = 'user_status_audit_log';
    protected $fillable = ['user_id', 'status', 'status_changed_at', 'created_by', 'updated_by'];
    public $timestamps = false;
}
