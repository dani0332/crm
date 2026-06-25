<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use App\Services\SageFailedRecordsService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Paginator;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

function sageFailedProcessesDropdownGIGInsurer(): array
{
    return [
        'insuranceProviders' => [['id' => 1, 'text' => 'GIG Insurer']],
        'quoteTypes' => [['id' => '1', 'text' => 'Car']],
        'options' => [
            ['id' => 'Main Lead', 'text' => 'Main Lead'],
            ['id' => 'Send Update', 'text' => 'Send Update'],
        ],
        'leadStatusOptions' => [
            ['id' => 'Policy Booking Failed', 'text' => 'Policy Booking Failed + Sage API Failed'],
            ['id' => 'Other Status', 'text' => 'Other Status + Sage API Failed'],
        ],
    ];
}

test('user without sage issue permission cannot access sage failed processes index', function () {
    $user = TestDataSeeder::createAdminUser(
        ['email' => fake()->unique()->safeEmail()],
        [PermissionsEnum::CustomersList],
    );
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->actingAs($user)
        ->get(route('sage-failed-processes.index'))
        ->assertForbidden();
});

test('validated filter query parameters are forwarded to inertia props', function () {
    $user = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $paginator = new Paginator([], 10, 1);

    $this->mock(SageFailedRecordsService::class, function ($mock) use ($paginator) {
        $mock->shouldReceive('getDropDownData')->once()->andReturn(sageFailedProcessesDropdownGIGInsurer());
        $mock->shouldReceive('getFailedSageRecords')->once()->andReturn($paginator);
    });

    $this->actingAs($user)
        ->get(route('sage-failed-processes.index', [
            'option' => 'Main Lead',
            'quote_type_id' => ['1', 'Send Update'],
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
            'lead_status_filter' => 'Policy Booking Failed',
            'date_filter_type' => ['Lead Created Date'],
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.option', 'Main Lead')
            ->where('filters.quote_type_id', ['1', 'Send Update'])
            ->where('filters.date_from', '2024-01-01')
            ->where('filters.date_to', '2024-01-31')
            ->where('filters.lead_status_filter', 'Policy Booking Failed')
            ->where('filters.date_filter_type', ['Lead Created Date'])
        );
});

test('lead status filter and date filter type are forwarded to inertia props', function () {
    $user = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $paginator = new Paginator([], 10, 1);

    $this->mock(SageFailedRecordsService::class, function ($mock) use ($paginator) {
        $mock->shouldReceive('getDropDownData')->once()->andReturn(sageFailedProcessesDropdownGIGInsurer());
        $mock->shouldReceive('getFailedSageRecords')->once()->andReturn($paginator);
    });

    $this->actingAs($user)
        ->get(route('sage-failed-processes.index', [
            'lead_status_filter' => 'Other Status',
            'date_filter_type' => ['Lead Created Date', 'Sage API Failure Date'],
            'date_from' => '2024-03-01',
            'date_to' => '2024-03-31',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.lead_status_filter', 'Other Status')
            ->where('filters.date_filter_type', ['Lead Created Date', 'Sage API Failure Date'])
            ->where('filters.date_from', '2024-03-01')
            ->where('filters.date_to', '2024-03-31')
        );
});

test('date_filter_type must be an array', function () {
    $user = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->mock(SageFailedRecordsService::class, function ($mock) {
        $mock->shouldNotReceive('getFailedSageRecords');
        $mock->shouldNotReceive('getDropDownData');
    });

    $this->actingAs($user)
        ->get(route('sage-failed-processes.index', [
            'date_filter_type' => 'Lead Created Date',
        ]))
        ->assertSessionHasErrors(['date_filter_type']);
});

test('invalid sage failed processes filters fail validation', function () {
    $user = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->mock(SageFailedRecordsService::class, function ($mock) {
        $mock->shouldNotReceive('getFailedSageRecords');
        $mock->shouldNotReceive('getDropDownData');
    });

    $this->actingAs($user)
        ->get(route('sage-failed-processes.index', [
            'date_from' => '2024-06-01',
            'date_to' => '2024-01-01',
        ]))
        ->assertSessionHasErrors(['date_to']);
});

test('invalid insurance provider id fails validation', function () {
    $user = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->mock(SageFailedRecordsService::class, function ($mock) {
        $mock->shouldNotReceive('getFailedSageRecords');
        $mock->shouldNotReceive('getDropDownData');
    });

    $this->actingAs($user)
        ->get(route('sage-failed-processes.index', [
            'insurance_provider_id' => [999_999_999],
        ]))
        ->assertSessionHasErrors(['insurance_provider_id.0']);
});

test('export returns 404 json when there is nothing to export', function () {
    $user = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->mock(SageFailedRecordsService::class, function ($mock) {
        $mock->shouldReceive('getFailedSageRecords')
            ->once()
            ->withArgs(fn ($safe, $isExport) => $isExport === true)
            ->andReturn(new EloquentCollection([]));
    });

    $this->actingAs($user)
        ->getJson(route('sage-failed-processes.export'))
        ->assertNotFound()
        ->assertJsonFragment(['message' => 'No data available to export.']);
});
