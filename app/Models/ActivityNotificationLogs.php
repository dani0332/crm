<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ActivityNotificationLogs extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'activity_notification_logs';
    protected $fillable = [
        'activity_id',
        'advisor_id',
        'notification_type',
    ];
}
