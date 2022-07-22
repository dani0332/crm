<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuoteDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'quote_documents';
    protected $guarded = [];
    protected $dates = ['deleted_at'];
    protected $fillable = ['doc_name','doc_url','doc_mime_type','document_type_code','created_by_id', 'updated_by_id'];
    protected $hidden = [''];


    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class,'created_by_id','id');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class,'updated_by_id','id');
    }
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
    public function quoteDocumentable()
    {
        return $this->morphTo();
    }
}
