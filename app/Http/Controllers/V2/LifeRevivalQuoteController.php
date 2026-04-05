<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\LifeRevivalService;
use Inertia\Response;

class LifeRevivalQuoteController extends Controller
{
    public function __construct(
        protected LifeRevivalService $lifeRevivalService,
    ) {}

    public function index(): Response
    {
        return inertia('LifeRevivalQuote/Index', [
            'quotes' => $this->lifeRevivalService->getPaginatedRevivalQuotes(),
            'formOptions' => $this->lifeRevivalService->getIndexFormOptions(),
        ]);
    }
}
