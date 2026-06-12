<?php

declare(strict_types=1);

use App\Events\CustomerUploadCompleted;
use App\Http\Controllers\V2\CustomerController;
use App\Http\Middleware\CheckRouteAccess;
use App\Http\Middleware\PreventRequestForgery;
use App\Http\Requests\CustomerUploadRequest;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Jobs\ProcessCustomerUploadJob;
use App\Models\Customer;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    $this->withoutMiddleware([
        PreventRequestForgery::class,
        CheckRouteAccess::class,
    ]);
});

afterEach(function (): void {
    Mockery::close();
});

test('processCustomerUpload stores file and dispatches ProcessCustomerUploadJob then redirects', function (): void {
    Bus::fake();
    Storage::fake();

    $user = TestDataSeeder::createUser();
    $file = UploadedFile::fake()->create(
        'customers.xlsx',
        100,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    );

    $request = Mockery::mock(CustomerUploadRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'myalfred_expiry_date' => '2026-12-31',
        'cdb_id' => 'CDB-001',
        'invitation_email' => true,
    ]);
    $request->shouldReceive('hasFile')->with('file_name')->andReturn(true);
    $request->shouldReceive('file')->with('file_name')->andReturn($file);

    $this->actingAs($user);

    $controller = new CustomerController;
    $response = $controller->processCustomerUpload($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toContain('customer-upload');

    Bus::assertDispatched(ProcessCustomerUploadJob::class);
});

test('ProcessCustomerUploadJob handle imports file, dispatches SQS jobs, and fires CustomerUploadCompleted', function (): void {
    Event::fake([CustomerUploadCompleted::class]);
    Bus::fake([ExtendCustomerSubscriptionViaSQS::class]);
    Storage::fake();

    $filePath = 'customer-uploads/test.xlsx';
    Storage::put($filePath, 'fake xlsx content');

    Excel::shouldReceive('import')->once()->andReturnUsing(function ($import): void {
        $import->rowCount = 42;
        $import->customersToExtend = [
            Customer::factory()->make(['id' => 1, 'email' => 'a@test.com']),
            Customer::factory()->make(['id' => 2, 'email' => 'b@test.com']),
        ];
    });

    $sendEmailService = Mockery::mock(SendEmailCustomerService::class);
    $berlinService = Mockery::mock(BerlinService::class);

    $job = new ProcessCustomerUploadJob(
        filePath: $filePath,
        myalfredExpiryDate: '2026-12-31',
        cdbId: 'CDB-001',
        invitationEmail: true,
        userId: 99,
    );

    $job->handle($sendEmailService, $berlinService);

    Storage::assertMissing($filePath);
    Bus::assertDispatched(ExtendCustomerSubscriptionViaSQS::class);
    Event::assertDispatched(CustomerUploadCompleted::class, function (CustomerUploadCompleted $event): bool {
        $data = $event->broadcastWith();

        return $data['status'] === 'success' && $data['userId'] === 99 && $data['uploadedCount'] === 42;
    });
});

test('ProcessCustomerUploadJob failed fires CustomerUploadCompleted with failed status', function (): void {
    Event::fake([CustomerUploadCompleted::class]);
    Storage::fake();

    $filePath = 'customer-uploads/test.xlsx';
    Storage::put($filePath, 'fake xlsx content');

    $job = new ProcessCustomerUploadJob(
        filePath: $filePath,
        myalfredExpiryDate: '2026-12-31',
        cdbId: 'CDB-002',
        invitationEmail: false,
        userId: 77,
    );

    $job->failed(new RuntimeException('Import error'));

    Storage::assertMissing($filePath);

    Event::assertDispatched(CustomerUploadCompleted::class, function (CustomerUploadCompleted $event): bool {
        $data = $event->broadcastWith();

        return $data['status'] === 'failed'
            && $data['userId'] === 77
            && $data['uploadedCount'] === 0
            && $data['cdbId'] === 'CDB-002';
    });
});
