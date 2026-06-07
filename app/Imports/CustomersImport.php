<?php

namespace App\Imports;

use App\Jobs\SyncCustomerJob;
use App\Models\Customer;
use App\Models\QuoteCustomer;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    public int $rowCount = 0;

    /** @var array<int, object> */
    public array $customersToExtend = [];

    public function __construct(
        public string $myalfredExpiryDate,
        public string $cdbId,
        public bool $invitationEmail,
        public SendEmailCustomerService $sendEmailCustomerService,
        public BerlinService $berlinService,
    ) {}

    public function collection(Collection $rows): void
    {
        $expiryDate = date('Y-m-d H:i:s', strtotime(str_replace('"', '', $this->myalfredExpiryDate)));
        $now = now()->toDateTimeString();

        $validRows = $rows->filter(
            fn ($row) => ! empty(trim((string) ($row['email'] ?? ''))) && isValidEmail(trim((string) $row['email']))
        );

        if ($validRows->isEmpty()) {
            return;
        }

        $emails = $validRows->map(fn ($row) => strtolower(trim((string) $row['email'])))->values();

        $existingByEmail = Customer::whereIn('email', $emails)
            ->select('email', 'myalfred_expiry_date')
            ->get()
            ->keyBy('email');

        $upsertData = $validRows->map(function ($row) use ($expiryDate, $existingByEmail, $now) {
            $email = strtolower(trim((string) $row['email']));
            $existing = $existingByEmail->get($email);
            $nameParts = explode(' ', $this->resolveNameFromRow($row), 2);

            return [
                'uuid' => Str::uuid()->toString(),
                'email' => $email,
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'has_alfred_access' => true,
                'has_reward_access' => true,
                'myalfred_expiry_date' => ($existing && $existing->myalfred_expiry_date >= $expiryDate)
                    ? $existing->myalfred_expiry_date
                    : $expiryDate,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        Customer::upsert(
            $upsertData,
            ['email'],
            ['first_name', 'last_name', 'myalfred_expiry_date', 'updated_at']
        );

        $customers = Customer::whereIn('email', $emails->all())->select('id', 'email')->get();

        $this->rowCount += $customers->count();

        QuoteCustomer::insert(
            $customers->map(fn ($c) => [
                'cdb_id' => $this->cdbId,
                'customer_id' => $c->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        $newCustomers = $customers->filter(fn ($c) => ! $existingByEmail->has($c->email));
        foreach ($newCustomers as $customer) {
            SyncCustomerJob::dispatch($customer->id, $customer->email);
        }

        Log::info('CustomerImport: chunk processed', [
            'count' => $customers->count(),
            'cdb_id' => $this->cdbId,
        ]);

        foreach ($customers as $customer) {
            $this->customersToExtend[] = (object) ['id' => $customer->id, 'email' => $customer->email];
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    /** Resolve the customer name from a row regardless of header casing or spacing. */
    private function resolveNameFromRow(mixed $row): string
    {
        // WithHeadingRow slugifies headers: "Full Name" → "full_name", "name" → "name"
        foreach (['name', 'full_name', 'customer_name'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
