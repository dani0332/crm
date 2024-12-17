<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class QuoteDocument extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'quote_documents';
    protected $guarded = [];
    protected $casts = [
        'deleted_at' => 'datetime',
    ];
    protected $fillable = ['doc_name', 'doc_url', 'doc_mime_type', 'document_type_code', 'document_type_text', 'doc_uuid', 'created_by_id', 'original_name', 'member_detail_id', 'payment_split_type', 'payment_split_id', 'watermarked_doc_name', 'watermarked_doc_url'];
    protected $hidden = [''];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.DATETIME_DISPLAY_FORMAT');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id', 'id');
    }

    public function quoteDocumentable()
    {
        return $this->morphTo();
    }

    public function notes()
    {
        return $this->belongsToMany(QuoteNote::class, 'document_note', 'document_id', 'note_id');
    }

    public function paymentDocuments()
    {
        return $this->belongsTo(PaymentSplits::class, 'payment_split_id', 'id');
    }
}
