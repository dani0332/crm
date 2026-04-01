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
    $this->lockMethod = new \ReflectionMethod($this->controller, 'responseIfBusinessQuoteMemberDetailsLocked');
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

test('it returns 403 json when business quote member details are locked (e.g. policy booked) and request is not inertia', function () {
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

    $request = Request::create('/', 'POST');
    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeInstanceOf(JsonResponse::class)
        ->and($result->getStatusCode())->toBe(403)
        ->and($result->getData(true))->toMatchArray([
            'error' => [
                'message' => 'You are not authorized to add member details for this quote.',
            ],
        ]);
});

test('it redirects back with error when business quote member details are locked and inertia request', function () {
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

    $request = Request::create('/', 'POST', [], [], [], [], json_encode([]));
    $request->merge(['isInertia' => true]);

    $quote = Mockery::mock(BusinessQuote::class);

    $result = $this->lockMethod->invoke($this->controller, $request, $quote);

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(session('error'))->toBe('You are not authorized to add member details for this quote.');
});
