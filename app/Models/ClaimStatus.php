<?php

namespace App\Models;

use App\Casts\ClaimStatusText;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ClaimStatus extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'claim_statuses';
    protected $fillable = ['text', 'description', 'is_active',  'sort_order', 'claim_request_type_id', 'quote_type_id', 'access_type_id', 'status_type'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'text' => ClaimStatusText::class,
        ];
    }
    /**
     * Scope to filter active records
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope to filter by status type
     */
    public function scopeByStatusType($query, string $statusType)
    {
        return $query->where('status_type', $statusType);
    }

    /**
     * Scope to filter by text
     */
    public function scopeByText($query, string $text)
    {
        return $query->where('text', $text);
    }

    /**
     * Scope to filter by quote type ID or null (general statuses)
     */
    public function scopeByQuoteType($query, ?int $quoteTypeId)
    {
        if ($quoteTypeId) {
            return $query->where(function ($q) use ($quoteTypeId) {
                $q->where('quote_type_id', $quoteTypeId)
                    ->orWhereNull('quote_type_id');
            });
        }

        return $query->whereNull('quote_type_id');
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrderBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
