<?php

namespace App\Repositories;

class DocumentQuoteRepository extends BaseRepository
{
    public function model()
    {
        return getModelNameForDocument(request()->folder_path ?? '');
    }
}
