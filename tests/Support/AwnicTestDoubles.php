<?php

namespace Tests\Support;

use App\Enums\AwnicEnum;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Database\Eloquent\Model;

class FakePolicyIssuanceProcess
{
    public ?string $completed_step = null;
    public ?string $status = null;
    public int $id = 101;

    public function __construct(public object $model)
    {
    }

    public function update(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function refresh(): self
    {
        return $this;
    }
}

class FakeAwnicQuote extends Model
{
    protected $guarded = [];

    public string $code = 'CYB-001';
    public ?string $policy_start_date = '2024-01-01';
    public object $customer;
    public object $nationality;
    public object $cyberQuote;
    public object $cyberPlanDetail;
    public array $latestInsured;
    public string $insurer_quote_number = 'AWNIC-EXT';
    public ?string $policy_number = 'POL123';
    public array $documents = [];

    private FakeAwnicPayment $payment;

    public function __construct()
    {
        $this->customer = (object) ['dob' => '1990-01-01'];
        $this->nationality = (object) ['awni_country_code' => 'UAE'];
        $this->cyberQuote = (object) ['emirateOfRegistration' => (object) ['text' => 'Dubai']];
        $this->cyberPlanDetail = (object) ['coverage' => 500000, 'planName' => 'Gold'];
        $this->latestInsured = ['id_type' => 'emiratesId', 'id_number' => '784-1984-1234567-1'];
        $this->payment = new FakeAwnicPayment;
    }

    public function payments(): FakeAwnicPaymentsRelation
    {
        return new FakeAwnicPaymentsRelation($this->payment);
    }
}

class FakeAwnicPaymentsRelation
{
    public function __construct(private FakeAwnicPayment $payment)
    {
    }

    public function mainLeadPayment(): FakeAwnicPaymentQuery
    {
        return new FakeAwnicPaymentQuery($this->payment);
    }
}

class FakeAwnicPaymentQuery
{
    public function __construct(private FakeAwnicPayment $payment)
    {
    }

    public function first(): FakeAwnicPayment
    {
        return $this->payment;
    }
}

class FakeAwnicPayment
{
    public string $reference = 'PAY-123';

    public function paymentSplits(): FakeAwnicPaymentSplitQuery
    {
        return new FakeAwnicPaymentSplitQuery((object) ['reference' => 'SPLIT-1']);
    }
}

class FakeAwnicPaymentSplitQuery
{
    public function __construct(private object $split)
    {
    }

    public function where($column, $value): self
    {
        return $this;
    }

    public function first(): object
    {
        return $this->split;
    }
}

class FakePolicyIssuanceService extends PolicyIssuanceService
{
    public array $logs = [];

    public function storePolicyIssuanceLog(...$arguments): void
    {
        $this->logs[] = $arguments;
    }

    public function updateAPIIssuanceAndInsurerStatus(...$arguments): void
    {
        // no-op for tests
    }
}

class FakeApplicationStorageService
{
    public function getValueByKey(string $key): mixed
    {
        return true;
    }
}

