<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;

class UserStatusAuditLog extends Model
{
    protected $table = 'user_status_audit_log';
    protected $fillable = ['user_id', 'status', 'status_changed_at', 'created_by', 'updated_by'];
    public $timestamps = false;
    protected $appends = ['status_display'];
    protected $casts = [
        'status' => 'integer',
        'status_changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function statusChangedAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) {
                    return null;
                }

                $dateTimeFormat = Config::get('constants.datetime_format');

                return $this->asDateTime($value)->timezone(config('app.timezone'))->format($dateTimeFormat);
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => UserStatusEnum::getUserStatusText($this->status),
        );
    }
}
