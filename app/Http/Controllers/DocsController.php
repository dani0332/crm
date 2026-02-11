<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocsController extends Controller
{
    /**
     * Serve documentation from resources/docs. Only accessible by logged-in users.
     * Routes: GET /docs and GET /docs/{path} (e.g. booking-process/index.html).
     */
    public function show(?string $path = null): BinaryFileResponse
    {
        $path = $path === null || $path === '' ? 'index.html' : trim($path, '/');

        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new NotFoundHttpException;
        }

        $basePath = resource_path('docs');
        $fullPath = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (File::isDirectory($fullPath)) {
            $fullPath .= DIRECTORY_SEPARATOR.'index.html';
        }

        if (! File::exists($fullPath) || ! File::isFile($fullPath)) {
            throw new NotFoundHttpException;
        }

        $realPath = realpath($fullPath);
        $realBase = realpath($basePath);
        if ($realPath === false || $realBase === false || ! str_starts_with($realPath, $realBase)) {
            throw new NotFoundHttpException;
        }

        return response()->file($realPath);
    }
}
