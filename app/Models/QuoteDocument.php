<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Config;

class QuoteDocument extends Model
{
    use HasFactory;

    protected $table = 'quote_documents';
    protected $guarded = [];

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
        return $this->belongsTo(QuoteDocumentType::class,'document_type_code','code');
    }
}
