<?php

declare(strict_types=1);

use App\Http\Controllers\AuditableController;
use App\Services\BaseService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;

afterEach(function () {
    Mockery::close();
});

test('loadAuditableComponent returns JSON from baseService audits when jsonData is present', function () {
    $auditsPayload = collect([['id' => 10, 'event' => 'updated']]);

    $baseService = Mockery::mock(BaseService::class);
    $baseService->shouldReceive('audits')
        ->once()
        ->with(42, 'App\\Models\\CarQuote')
        ->andReturn($auditsPayload);

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);

    $controller = new AuditableController($baseService, $policyIssuanceService);

    $request = Request::create('/auditable', 'POST', [
        'auditableId' => 42,
        'auditableType' => 'App\Models\CarQuote',
        'jsonData' => true,
    ]);

    $response = $controller->loadAuditableComponent($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toContain('application/json')
        ->and(json_decode($response->getContent(), true))->toBe($auditsPayload->toArray());
});

test('loadAuditableComponent returns auditable view and does not call audits when jsonData is absent', function () {
    $view = Mockery::mock(ViewContract::class);
    $view->shouldReceive('name')->andReturn('auditable');
    $view->shouldReceive('getData')->andReturn([
        'auditableId' => 99,
        'auditableType' => 'App\Models\Payment',
    ]);

    $viewFactory = Mockery::mock(ViewFactory::class);
    $viewFactory->shouldReceive('make')
        ->once()
        ->with('auditable', [
            'auditableId' => 99,
            'auditableType' => 'App\Models\Payment',
        ], [])
        ->andReturn($view);

    $this->instance('view', $viewFactory);

    $baseService = Mockery::mock(BaseService::class);
    $baseService->shouldNotReceive('audits');

    $policyIssuanceService = Mockery::mock(PolicyIssuanceService::class);

    $controller = new AuditableController($baseService, $policyIssuanceService);

    $request = Request::create('/auditable', 'POST', [
        'auditableId' => 99,
        'auditableType' => 'App\Models\Payment',
    ]);

    $response = $controller->loadAuditableComponent($request);

    expect($response->name())->toBe('auditable')
        ->and($response->getData())->toMatchArray([
            'auditableId' => 99,
            'auditableType' => 'App\Models\Payment',
        ]);
});
