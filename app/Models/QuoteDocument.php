<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quote_documents';
    protected $guarded = [];
    protected $dates = ['deleted_at'];
    protected $fillable = ['doc_name', 'doc_url', 'doc_mime_type', 'document_type_code', 'document_type_text', 'doc_uuid', 'created_by_id', 'original_name'];
    protected $hidden = [''];

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getUpdatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
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
}
