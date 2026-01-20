<?php

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Events\AuthorisedPaymentCountUpdated;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Models\User;
use App\Models\UserTeams;
use App\Repositories\PaymentRepository;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);

    // Create an advisor user
    $this->advisor = TestDataSeeder::createUser([
        'name' => 'Test Advisor',
        'email' => 'advisor@test.com',
    ]);

    // Create a team and assign advisor to it (required for count query)
    $team = Team::create([
        'name' => 'Test Team',
        'type' => 'TEAM',
        'is_active' => 1,
    ]);

    UserTeams::create([
        'user_id' => $this->advisor->id,
        'team_id' => $team->id,
    ]);

    // Create a PersonalQuote with the advisor
    $this->personalQuote = PersonalQuote::create([
        'advisor_id' => $this->advisor->id,
        'quote_type_id' => QuoteTypeId::Car,
        'code' => 'TEST-QUOTE-'.uniqid(),
        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'mobile_no' => '+971501234567',
        'source' => 'TEST',
        'device' => 'WEB',
    ]);

    Event::fake([AuthorisedPaymentCountUpdated::class]);
});

test('payment observer broadcasts authorised payment count when payment status changes to authorised', function () {
    // Create a payment with non-authorized status
    $payment = Payment::create([
        'code' => $this->personalQuote->code,
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Update payment status to AUTHORISED
    $payment->update(['payment_status_id' => PaymentStatusEnum::AUTHORISED]);

    // Assert that the event was dispatched for the advisor
    Event::assertDispatched(AuthorisedPaymentCountUpdated::class, function ($event) {
        return $event->broadcastWith()['userId'] === $this->advisor->id;
    });
});

test('payment observer does not broadcast for non-personal quote payments', function () {
    // Create a CarQuote (not PersonalQuote)
    $carQuote = \App\Models\CarQuote::factory()->create([
        'advisor_id' => $this->advisor->id,
        'code' => 'CAR-QUOTE-'.uniqid(),
    ]);

    // Create a payment for CarQuote
    $payment = Payment::create([
        'code' => $carQuote->code,
        'paymentable_id' => $carQuote->id,
        'paymentable_type' => \App\Models\CarQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Update payment status to AUTHORISED
    $payment->update(['payment_status_id' => PaymentStatusEnum::AUTHORISED]);

    // Assert that the event was NOT dispatched
    Event::assertNotDispatched(AuthorisedPaymentCountUpdated::class);
});

test('payment observer does not broadcast when payment status changes to non-authorized status', function () {
    // Create a payment with PENDING status
    $payment = Payment::create([
        'code' => $this->personalQuote->code,
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Update payment status to PAID (not AUTHORISED)
    $payment->update(['payment_status_id' => PaymentStatusEnum::PAID]);

    // Assert that the event was NOT dispatched
    Event::assertNotDispatched(AuthorisedPaymentCountUpdated::class);
});

test('notification service broadcasts authorised payment count when webhook is called with authorised payment', function () {
    // Create a payment with AUTHORISED status
    Payment::create([
        'code' => $this->personalQuote->code,
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::AUTHORISED,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Call the webhook endpoint
    $response = $this->postJson('/payments/update-payment-status', [
        'quoteType' => 'Car',
        'quoteId' => $this->personalQuote->uuid,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['message' => 'Payment notification successfully sent to advisor']);

    // Assert that the event was dispatched for the advisor
    Event::assertDispatched(AuthorisedPaymentCountUpdated::class, function ($event) {
        return $event->broadcastWith()['userId'] === $this->advisor->id;
    });
});

test('notification service does not broadcast when quote has no authorised payment', function () {
    // Create a payment with PENDING status (not AUTHORISED)
    Payment::create([
        'code' => $this->personalQuote->code,
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Call the webhook endpoint
    $response = $this->postJson('/payments/update-payment-status', [
        'quoteType' => 'Car',
        'quoteId' => $this->personalQuote->uuid,
    ]);

    $response->assertStatus(200);

    // Assert that the event was NOT dispatched
    Event::assertNotDispatched(AuthorisedPaymentCountUpdated::class);
});

test('notification service does not broadcast for non-personal quotes', function () {
    // Create a CarQuote (not PersonalQuote)
    $carQuote = \App\Models\CarQuote::factory()->create([
        'advisor_id' => $this->advisor->id,
        'code' => 'CAR-QUOTE-'.uniqid(),
        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    // Create a payment with AUTHORISED status
    Payment::create([
        'code' => $carQuote->code,
        'paymentable_id' => $carQuote->id,
        'paymentable_type' => \App\Models\CarQuote::class,
        'payment_status_id' => PaymentStatusEnum::AUTHORISED,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Call the webhook endpoint
    $response = $this->postJson('/payments/update-payment-status', [
        'quoteType' => 'Car',
        'quoteId' => $carQuote->uuid,
    ]);

    $response->assertStatus(200);

    // Assert that the event was NOT dispatched (because it's not a PersonalQuote)
    Event::assertNotDispatched(AuthorisedPaymentCountUpdated::class);
});

test('authorised payment count is calculated correctly for advisor', function () {
    // Create multiple payments with AUTHORISED status for the advisor
    for ($i = 0; $i < 3; $i++) {
        Payment::create([
            'code' => $this->personalQuote->code.'-'.$i,
            'paymentable_id' => $this->personalQuote->id,
            'paymentable_type' => PersonalQuote::class,
            'payment_status_id' => PaymentStatusEnum::AUTHORISED,
            'total_price' => 1000,
            'total_amount' => 1000,
        ]);
    }

    // Create another PersonalQuote with the same advisor
    $anotherQuote = PersonalQuote::create([
        'advisor_id' => $this->advisor->id,
        'quote_type_id' => QuoteTypeId::Car,
        'code' => 'TEST-QUOTE-2-'.uniqid(),
        'uuid' => \Illuminate\Support\Str::uuid()->toString(),
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test2@example.com',
        'mobile_no' => '+971501234568',
        'source' => 'TEST',
        'device' => 'WEB',
    ]);

    Payment::create([
        'code' => $anotherQuote->code,
        'paymentable_id' => $anotherQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::AUTHORISED,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Calculate count
    $paymentRepository = app(PaymentRepository::class);
    $count = $paymentRepository->getAuthorisePaymentCount($this->advisor);

    // Should have 4 authorised payments (3 from first quote + 1 from second quote)
    expect($count)->toBe(4);
});

test('authorised payment count excludes non-authorized payments', function () {
    // Create payments with different statuses
    Payment::create([
        'code' => $this->personalQuote->code,
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::AUTHORISED,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    Payment::create([
        'code' => $this->personalQuote->code.'-2',
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    Payment::create([
        'code' => $this->personalQuote->code.'-3',
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::PAID,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Calculate count
    $paymentRepository = app(PaymentRepository::class);
    $count = $paymentRepository->getAuthorisePaymentCount($this->advisor);

    // Should only count the AUTHORISED payment
    expect($count)->toBe(1);
});

test('authorised payment count event contains correct data', function () {
    // Create a payment with PENDING status
    $payment = Payment::create([
        'code' => $this->personalQuote->code,
        'paymentable_id' => $this->personalQuote->id,
        'paymentable_type' => PersonalQuote::class,
        'payment_status_id' => PaymentStatusEnum::PENDING,
        'total_price' => 1000,
        'total_amount' => 1000,
    ]);

    // Update payment status to AUTHORISED
    $payment->update(['payment_status_id' => PaymentStatusEnum::AUTHORISED]);

    // Assert event data
    Event::assertDispatched(AuthorisedPaymentCountUpdated::class, function ($event) {
        $data = $event->broadcastWith();
        expect($data['userId'])->toBe($this->advisor->id);
        expect($data['count'])->toBeInt();
        expect($data['count'])->toBeGreaterThanOrEqual(0);

        return true;
    });
});
