<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FilterTypes;
use App\Traits\FilterCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Generic Document Type Model
 *
 * This model represents document types that can be used across different modules
 * for generic document categorization and management.
 */
class GenericDocumentType extends Model implements AuditableContract
{
    use Auditable, FilterCriteria, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'generic_document_types';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'text',
        'description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Filterable attributes for search functionality.
     */
    protected array $filterables = [
        'code' => FilterTypes::FREE,
        'text' => FilterTypes::FREE,
        'description' => FilterTypes::FREE,
    ];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();
    }

    /**
     * Scope to filter by code.
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to search by text.
     */
    public function scopeByText($query, string $text)
    {
        return $query->where('text', 'like', "%{$text}%");
    }

    /**
     * Find or create a document type by code.
     */
    public static function findOrCreate(string $code): self
    {
        $obj = static::where('code', $code)->first();

        return $obj ?: new static(['code' => $code]);
    }

    /**
     * Get formatted created at attribute.
     */
    public function getCreatedAtAttribute($value): string
    {
        $date_time_format = config('constants.datetime_format', 'Y-m-d H:i:s');

        return $this->asDateTime($value)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * Get formatted updated at attribute.
     */
    public function getUpdatedAtAttribute($value): string
    {
        $date_time_format = config('constants.datetime_format', 'Y-m-d H:i:s');

        return $this->asDateTime($value)->timezone(config('app.timezone'))->format($date_time_format);
    }

    /**
     * Relationship with generic documents.
     */
    public function genericDocuments()
    {
        return $this->hasMany(GenericDocument::class, 'document_type_id');
    }
}
