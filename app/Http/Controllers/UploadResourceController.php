<?php

namespace App\Http\Controllers;

use App\Services\UploadResourceService;
use Illuminate\Http\Request;

class UploadResourceController extends ApiController
{
    /**
     * @var ResourcesService
     */
    private $resourcesUploadService;

    /**
     * UploadResourceController constructor.
     *
     * @param  ResourcesUploadService  $resourcesUploadService
     */
    public function __construct(UploadResourceService $resourcesUploadService)
    {
        $this->resourcesUploadService = $resourcesUploadService;
    }

    public function store(Request $request)
    {
        $result = $this->resourcesUploadService->createResource($request);

        return $this->respondData($result);
    }
}
