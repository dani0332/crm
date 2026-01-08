<?php

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\DeviceQuote;
use App\Models\PersonalQuote;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Helpers\DeviceQuoteMockHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    
    // Seed device quote permissions
    $permissions = [
        PermissionsEnum::DEVICE_QUOTES_LIST,
        PermissionsEnum::DEVICE_QUOTES_CREATE,
        PermissionsEnum::DEVICE_QUOTES_EDIT,
        PermissionsEnum::DEVICE_QUOTES_SHOW,
    ];
    
    foreach ($permissions as $permissionName) {
        $permission = Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web',
        ]);
        
        $adminRole = Role::where('name', RolesEnum::Admin)->first();
        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }
    }
    
    // Clear permission cache
    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    
    // Refresh user to reload permissions
    $this->user->refresh();
    
    $this->actingAs($this->user);
});

afterEach(function () {
    Mockery::close();
});

test('can create a device quote using actual controller method', function () {
    $testUuid = 'test-device-quote-uuid-'.uniqid();
    $quoteData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@gmail.com',
        'mobile_no' => '+971501234567',
        'month_of_purchase' => '01',
        'year_of_purchase' => '2023',
        'make_id' => 1,
        'model_id' => 1,
        'imei' => '123456789012345',
    ];

    DeviceQuoteMockHelper::mockCapiService($testUuid);

    $response = $this->post(route('device-quotes-store'), $quoteData);

    $response->assertRedirect(route('device-quotes-show', $testUuid));
    $response->assertSessionHas('message', 'Quote is created successfully.');

    $personalQuote = PersonalQuote::where('uuid', $testUuid)->firstOrFail();
    expect($personalQuote->first_name)->toBe($quoteData['first_name'])
        ->and($personalQuote->last_name)->toBe($quoteData['last_name'])
        ->and($personalQuote->email)->toBe($quoteData['email'])
        ->and($personalQuote->quote_type_id)->toBe(QuoteTypeId::Device)
        ->and($personalQuote->created_by_id)->toBe($this->user->id);

    $deviceQuote = $personalQuote->deviceQuote;
    expect($deviceQuote)->not->toBeNull()
        ->and($deviceQuote->first_name)->toBe($quoteData['first_name'])
        ->and($deviceQuote->last_name)->toBe($quoteData['last_name'])
        ->and($deviceQuote->email)->toBe($quoteData['email'])
        ->and($deviceQuote->mobile_no)->toBe($quoteData['mobile_no'])
        ->and($deviceQuote->month_of_purchase)->toBe($quoteData['month_of_purchase'])
        ->and($deviceQuote->year_of_purchase)->toBe($quoteData['year_of_purchase'])
        ->and($deviceQuote->make_id)->toBe($quoteData['make_id'])
        ->and($deviceQuote->model_id)->toBe($quoteData['model_id'])
        ->and($deviceQuote->imei)->toBe($quoteData['imei']);
});

test('validates required fields when creating device quote', function () {
    $response = $this->post(route('device-quotes-store'), []);

    $response->assertSessionHasErrors([
        'first_name',
        'last_name',
        'email',
        'mobile_no',
        'month_of_purchase',
        'year_of_purchase',
        'make_id',
        'model_id',
        'imei',
    ]);

    $response->assertStatus(302);
});
