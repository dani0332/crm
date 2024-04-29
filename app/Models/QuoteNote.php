<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class QuoteNote extends Model implements AuditableContract
{
    use Auditable , HasFactory;

    protected $fillable = ['quote_status_id', 'note', 'created_by', 'updated_by'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'quote_status_id', 'id');
    }

    public function quoteNoteable()
    {
        return $this->morphTo();
    }

    public function documents()
    {
        return $this->belongsToMany(QuoteDocument::class, 'document_note', 'note_id', 'document_id');
    }
}
