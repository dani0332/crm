<?php

use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeId;
use App\Http\Middleware\BasicAuth;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(BasicAuth::class);

    if (! Schema::hasTable('email_status')) {
        Schema::create('email_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_type_id')->nullable();
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->string('email_address')->nullable();
            $table->string('msg_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('email_status')->nullable();
            $table->string('email_subject')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->boolean('customer_replied')->default(0);
            $table->timestamps();
        });
    }
});

afterEach(function () {
    if (Schema::hasTable('email_status')) {
        Schema::drop('email_status');
    }
});

test('postmark delivery updates email_status by MessageID', function () {
    $messageId = 'a1111111-b222-c333-d444-e55555555555';

    DB::connection('sqlite')->table('email_status')->insert([
        'quote_type_id' => QuoteTypeId::Car,
        'quote_id' => 42,
        'email_address' => 'cust@example.com',
        'msg_id' => $messageId,
        'reason' => null,
        'email_status' => ProcessStatusCode::IN_PROGRESS,
        'email_subject' => 'EP cert',
        'template_id' => null,
        'customer_id' => null,
        'customer_replied' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Delivery',
        'MessageID' => $messageId,
        'Recipient' => 'cust@example.com',
    ])->assertOk()->assertJson([
        'data' => null,
        'message' => 'Email statuses logged successfully',
        'status' => 200,
    ]);

    $row = DB::connection('sqlite')->table('email_status')->where('msg_id', $messageId)->first();
    expect($row->email_status)->toBe(ProcessStatusCode::SENT);
});

test('postmark bounce sets failed and reason', function () {
    $messageId = 'b1111111-b222-c333-d444-e55555555555';

    DB::connection('sqlite')->table('email_status')->insert([
        'quote_type_id' => QuoteTypeId::Car,
        'quote_id' => 99,
        'email_address' => 'bad@example.com',
        'msg_id' => $messageId,
        'reason' => null,
        'email_status' => ProcessStatusCode::IN_PROGRESS,
        'email_subject' => 'EP cert',
        'template_id' => null,
        'customer_id' => null,
        'customer_replied' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Bounce',
        'MessageID' => $messageId,
        'Email' => 'bad@example.com',
        'Description' => 'Mailbox full',
    ])->assertOk();

    $row = DB::connection('sqlite')->table('email_status')->where('msg_id', $messageId)->first();
    expect($row->email_status)->toBe(ProcessStatusCode::FAILED);
});

test('postmark creates row from Metadata when no msg_id match', function () {
    $messageId = 'c1111111-b222-c333-d444-e55555555555';

    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Delivery',
        'MessageID' => $messageId,
        'Recipient' => 'new@example.com',
        'Metadata' => [
            'quote_id' => '7',
            'quote_type_id' => (string) QuoteTypeId::Car,
            'subject' => 'Welcome EP',
        ],
    ])->assertOk();

    $row = DB::connection('sqlite')->table('email_status')->where('msg_id', $messageId)->first();
    expect($row)->not->toBeNull()
        ->and((int) $row->quote_id)->toBe(7)
        ->and((int) $row->quote_type_id)->toBe(QuoteTypeId::Car)
        ->and($row->email_status)->toBe(ProcessStatusCode::SENT)
        ->and($row->email_subject)->toBe('Welcome EP');
});

test('postmark creates row from Metadata when quote ids are JSON numbers', function () {
    $messageId = 'f1111111-b222-c333-d444-e55555555555';

    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Delivery',
        'MessageID' => $messageId,
        'Recipient' => 'numeric@example.com',
        'Metadata' => [
            'quote_id' => 12,
            'quote_type_id' => QuoteTypeId::Car,
            'subject' => 'Numeric ids',
        ],
    ])->assertOk();

    $row = DB::connection('sqlite')->table('email_status')->where('msg_id', $messageId)->first();
    expect($row)->not->toBeNull()
        ->and((int) $row->quote_id)->toBe(12)
        ->and((int) $row->quote_type_id)->toBe(QuoteTypeId::Car)
        ->and($row->email_subject)->toBe('Numeric ids');
});

test('postmark open does not change stored status', function () {
    $messageId = 'd1111111-b222-c333-d444-e55555555555';

    DB::connection('sqlite')->table('email_status')->insert([
        'quote_type_id' => QuoteTypeId::Car,
        'quote_id' => 1,
        'email_address' => 'o@example.com',
        'msg_id' => $messageId,
        'reason' => null,
        'email_status' => ProcessStatusCode::IN_PROGRESS,
        'email_subject' => 'Subj',
        'template_id' => null,
        'customer_id' => null,
        'customer_replied' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Open',
        'MessageID' => $messageId,
        'Recipient' => 'o@example.com',
    ])->assertOk();

    $row = DB::connection('sqlite')->table('email_status')->where('msg_id', $messageId)->first();
    expect($row->email_status)->toBe(ProcessStatusCode::IN_PROGRESS);
});

test('postmark delivery without row or metadata does not insert', function () {
    $messageId = 'e1111111-b222-c333-d444-e55555555555';

    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Delivery',
        'MessageID' => $messageId,
        'Recipient' => 'orphan@example.com',
    ])->assertOk();

    expect(DB::connection('sqlite')->table('email_status')->where('msg_id', $messageId)->count())->toBe(0);
});

test('validation fails without MessageID', function () {
    $this->postJson('/api/v1/log-ep-email-statuses', [
        'RecordType' => 'Delivery',
    ])->assertStatus(422);
});
