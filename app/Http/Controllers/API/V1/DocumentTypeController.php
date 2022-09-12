<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentTypeResource;
use App\Services\DocumentTypeService;
use Illuminate\Http\Request;

class DocumentTypeController extends Controller
{
    /**
     * get list of active document types can be presented to customer to upload documents
     * @param DocumentTypeService $documentTypeService
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getCustomerAllowedTypes(DocumentTypeService $documentTypeService)
    {
        $documentTypes = $documentTypeService->getCustomerAllowedTypes();
        return DocumentTypeResource::collection($documentTypes);
    }
}
