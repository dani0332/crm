<?php

declare(strict_types=1);

use App\Exports\SageProcessesExport;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use Illuminate\Support\Collection;

test('SageProcessesExport map reads export fields from failed-records shape', function (): void {
    $row = (object) [
        'id' => 501,
        'model_type' => PersonalQuote::class,
        'status' => null,
        'collected_sage_receipt_ids' => null,
        'sage_api_status' => 'FAIL',
        'failed_api' => '/api/sage/test',
        'failed_error' => '{"error":"Sage failure"}',
        'imcrm_error' => 'IMCRM validation failed',
        'insuranceProvider' => (object) ['text' => 'GIG Insurer'],
        'model' => (object) [
            'uuid' => 'uuid-123',
            'code' => 'REF-123',
            'created_at' => '2025-07-02 13:09:00',
            'policy_number' => 'POL-123',
            'quoteStatus' => (object) ['text' => 'Policy Booking Failed'],
            'payments' => [
                (object) [
                    'price_vat_applicable' => 'Yes',
                    'price_vat' => '15',
                    'discount_value' => '5',
                    'total_price' => '100',
                    'commission_vat_applicable' => 'Yes',
                    'commission_vat' => '8',
                    'commission' => '40',
                    'captured_at' => '2025-07-02 01:15pm',
                    'paymentStatus' => (object) ['text' => 'Paid'],
                    'invoice_description' => 'Invoice details',
                    'insurer_tax_number' => 'TX-123',
                    'insurer_commmission_invoice_number' => 'CTX-456',
                    'collected_sage_receipt_ids' => 'R-1,R-2',
                ],
            ],
        ],
    ];

    $export = new SageProcessesExport(new Collection([$row]));

    $mapped = $export->map($row);

    expect($mapped[20])->toBe('R-1,R-2')
        ->and($mapped[21])->toBe('FAIL')
        ->and($mapped[22])->toBe('/api/sage/test')
        ->and($mapped[23])->toBe('{"error":"Sage failure"}')
        ->and($mapped[24])->toBe('IMCRM validation failed');
});

test('SageProcessesExport map sets Send Update ref columns correctly', function (): void {
    $row = (object) [
        'id' => 777,
        'model_type' => SendUpdateLog::class,
        'sage_api_status' => 'FAIL',
        'failed_api' => '/api/sage/send-update',
        'failed_error' => '{"error":"Send update failed"}',
        'imcrm_error' => 'Send update failed',
        'insuranceProvider' => (object) ['text' => 'GIG Insurer'],
        'model' => (object) [
            'code' => 'SU-REF-001',
            'created_at' => '2025-08-01 09:30:00',
            'personalQuote' => (object) [
                'code' => 'REF-001',
            ],
            'payments' => [],
        ],
    ];

    $export = new SageProcessesExport(new Collection([$row]));

    $mapped = $export->map($row);

    expect($mapped[2])->toBe('REF-001')
        ->and($mapped[3])->toBe('SU-REF-001');
});
