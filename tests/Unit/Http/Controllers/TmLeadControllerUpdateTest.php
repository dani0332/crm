<?php

declare(strict_types=1);

use App\Http\Controllers\TmLeadController;
use App\Http\Requests\TmLeadRequest;
use App\Models\TmLead;
use App\Services\TMLeadsService;
use Illuminate\Support\Facades\Route;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Route::name('tmleads-list')->get('/telemarketing/tmleads', fn () => 'ok');
});

test('tm advisor cannot update a lead assigned to another user', function () {
    $advisor = TestDataSeeder::createUserWithRole('TM_ADVISOR');
    $this->actingAs($advisor);

    $service = Mockery::mock(TMLeadsService::class);
    $service->shouldNotReceive('tmLeadsCreateUpdate');

    $controller = new TmLeadController($service);

    $request = TmLeadRequest::create('/telemarketing/tmleads/99', 'PUT', [
        'customer_name' => 'Test User',
        'phone_number' => '+971500000000',
        'email_address' => 'test@example.com',
        'tm_insurance_types_id' => 1,
        'enquiry_date' => '2026-03-31',
        'allocation_date' => '2026-03-31',
        'tm_lead_types_id' => 1,
    ]);
    $request->setUserResolver(fn () => $advisor);

    $tmLead = new TmLead([
        'id' => 99,
        'assigned_to_id' => 99999,
    ]);

    $response = $controller->update($request, $tmLead);

    expect($response->getTargetUrl())->toContain('/telemarketing/tmleads')
        ->and(session('error'))->toBe("You don't have access to edit this lead");
});

test('tm advisor cannot view a lead assigned to another user', function () {
    $advisor = TestDataSeeder::createUserWithRole('TM_ADVISOR');
    $this->actingAs($advisor);

    $service = Mockery::mock(TMLeadsService::class);
    $controller = new TmLeadController($service);

    $tmLead = new TmLead([
        'id' => 199,
        'assigned_to_id' => 99999,
        'phone_number' => '+971500000000',
    ]);

    $response = $controller->show($tmLead);

    expect($response->getTargetUrl())->toContain('/telemarketing/tmleads')
        ->and(session('error'))->toBe("You don't have access to view this lead");
});
