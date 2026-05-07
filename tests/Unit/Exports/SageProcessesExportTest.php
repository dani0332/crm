<?php

declare(strict_types=1);

use App\Exports\SageProcessesExport;
use App\Models\EmbeddedTransaction;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use Illuminate\Support\Collection;

const SAGE_PROCESSES_EXPORT_PROVIDER = 'GIG Insurer';

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
        'insuranceProvider' => (object) ['text' => SAGE_PROCESSES_EXPORT_PROVIDER],
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

    expect($mapped[21])->toBe('R-1,R-2')
        ->and($mapped[22])->toBe('FAIL')
        ->and($mapped[23])->toBe('/api/sage/test')
        ->and($mapped[24])->toBe('{"error":"Sage failure"}')
        ->and($mapped[25])->toBe('IMCRM validation failed');
});

test('SageProcessesExport map sets Send Update ref columns correctly', function (): void {
    $row = (object) [
        'id' => 777,
        'model_type' => SendUpdateLog::class,
        'sage_api_status' => 'FAIL',
        'failed_api' => '/api/sage/send-update',
        'failed_error' => '{"error":"Send update failed"}',
        'imcrm_error' => 'Send update failed',
        'insuranceProvider' => (object) ['text' => SAGE_PROCESSES_EXPORT_PROVIDER],
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

test('SageProcessesExport map separates embedded transaction and parent quote ref columns', function (): void {
    $row = (object) [
        'id' => 778,
        'model_type' => EmbeddedTransaction::class,
        'sage_api_status' => 'FAIL',
        'failed_api' => '/api/sage/embedded',
        'failed_error' => '{"error":"Embedded product failed"}',
        'imcrm_error' => 'Embedded product failed',
        'insuranceProvider' => (object) ['text' => SAGE_PROCESSES_EXPORT_PROVIDER],
        'model' => (object) [
            'uuid' => null,
            'code' => 'MDX-CAR-EP001',
            'created_at' => '2025-08-01 09:30:00',
            'quoteRequest' => (object) [
                'uuid' => 'quote-uuid-001',
                'code' => 'CAR-PARENT001',
            ],
            'payments' => [],
        ],
    ];

    $export = new SageProcessesExport(new Collection([$row]));

    $mapped = $export->map($row);

    expect($mapped[1])->toBe('quote-uuid-001')
        ->and($mapped[2])->toBe('CAR-PARENT001')
        ->and($mapped[3])->toBe('N/A')
        ->and($mapped[4])->toBe('MDX-CAR-EP001');
});
