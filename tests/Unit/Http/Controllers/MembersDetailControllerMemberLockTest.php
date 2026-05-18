<?php

declare(strict_types=1);

use App\Http\Controllers\MembersDetailController;
use App\Models\BusinessQuote;
use App\Models\HealthQuote;
use App\Services\CentralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->controller = new MembersDetailController;
    $this->lockMethod = new ReflectionMethod($this->controller, 'responseIfBusinessQuoteMemberDetailsLocked');
    $this->lockMethod->setAccessible(true);
});

test('it does not block when quote is not a business quote', function () {
    $this->mock(CentralService::class)->shouldNotReceive('lockLeadSectionsDetails');

    $request = Request::create('/', 'POST');
    $quote = new HealthQuote;

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeNull();
});

test('it does not block business quote when member details are not locked (normal lead)', function () {
    $this->mock(CentralService::class, function ($mock) {
        $mock->shouldReceive('lockLeadSectionsDetails')
            ->once()
            ->andReturn([
                'member_details' => false,
                'plan_selection' => false,
                'plan_details' => false,
                'lead_status' => false,
                'lead_details' => false,
                'manage_payment' => false,
            ]);
    });

    $request = Request::create('/', 'POST');
    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeNull();
});

test('it redirects back with policy booked flash when business quote member details are locked', function () {
    $this->mock(CentralService::class, function ($mock) {
        $mock->shouldReceive('lockLeadSectionsDetails')
            ->once()
            ->andReturn([
                'member_details' => true,
                'plan_selection' => true,
                'plan_details' => false,
                'lead_status' => false,
                'lead_details' => false,
                'manage_payment' => false,
            ]);
    });

    $request = Request::create('/', 'POST', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(session('error'))->toBe(MembersDetailController::FLASH_ERROR_MEMBER_DETAILS_LOCKED);
});

test('it does not block when locked and from_aml_model is true', function () {
    $this->mock(CentralService::class, function ($mock) {
        $mock->shouldNotReceive('lockLeadSectionsDetails');
    });

    $request = Request::create('/', 'POST', ['from_aml_model' => true]);
    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeNull();
});

test('it returns json 403 when locked and client expects json without from_aml_model', function () {
    $this->mock(CentralService::class, function ($mock) {
        $mock->shouldReceive('lockLeadSectionsDetails')
            ->once()
            ->andReturn([
                'member_details' => true,
                'plan_selection' => true,
                'plan_details' => false,
                'lead_status' => false,
                'lead_details' => false,
                'manage_payment' => false,
            ]);
    });

    $request = Request::create('/api/members', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeInstanceOf(JsonResponse::class)
        ->and($result->getStatusCode())->toBe(403)
        ->and($result->getData(true))->toMatchArray([
            'status' => false,
            'message' => MembersDetailController::FLASH_ERROR_MEMBER_DETAILS_LOCKED,
        ]);
});

test('it redirects when locked and request has x-inertia (not from_aml_model)', function () {
    $this->mock(CentralService::class, function ($mock) {
        $mock->shouldReceive('lockLeadSectionsDetails')
            ->once()
            ->andReturn([
                'member_details' => true,
                'plan_selection' => true,
                'plan_details' => false,
                'lead_status' => false,
                'lead_details' => false,
                'manage_payment' => false,
            ]);
    });

    $request = Request::create('/', 'POST', [], [], [], [
        'HTTP_X_INERTIA' => 'true',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(session('error'))->toBe(MembersDetailController::FLASH_ERROR_MEMBER_DETAILS_LOCKED);
});
