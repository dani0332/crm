<?php

namespace App\Repositories;

use App\Models\DocumentType;

class DocumentTypeRepository extends BaseRepository
{
    public function model()
    {
        return DocumentType::class;
    }
}
