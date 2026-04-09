<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Console\Commands\ResetLeadAllocationCounts;
use App\Models\LeadAllocation;
use ReflectionMethod;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;
use Tests\TestCase;

class ResetLeadAllocationCountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestSchemaCreator::createMinimalSchema();

        SchemaUtils::addColumnIfMissing('lead_allocation', 'reset_cap', function ($table) {
            $table->integer('reset_cap')->default(0);
        });
    }

    public function test_sets_capacity_40_when_quote_type_id_is_1()
    {
        LeadAllocation::factory()->create([
            'reset_cap' => 1,
            'quote_type_id' => 1,
            'max_capacity' => 10,
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'quote_type_id' => 1,
            'max_capacity' => 40,
        ]);
    }

    public function test_sets_capacity_40_when_quote_type_id_is_3()
    {
        LeadAllocation::factory()->create([
            'reset_cap' => 1,
            'quote_type_id' => 3,
            'max_capacity' => 15,
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'quote_type_id' => 3,
            'max_capacity' => 40,
        ]);
    }

    public function test_sets_capacity_20_when_quote_type_id_is_not_1_or_3()
    {
        LeadAllocation::factory()->create([
            'reset_cap' => 1,
            'quote_type_id' => 2,
            'max_capacity' => 50,
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'quote_type_id' => 2,
            'max_capacity' => 20,
        ]);
    }

    public function test_only_updates_records_where_reset_cap_equals_1()
    {
        LeadAllocation::factory()->create([
            'reset_cap' => 0,
            'quote_type_id' => 1,
            'max_capacity' => 100,
        ]);

        LeadAllocation::factory()->create([
            'reset_cap' => 1,
            'quote_type_id' => 1,
            'max_capacity' => 10,
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'reset_cap' => 0,
            'max_capacity' => 100,
        ]);

        $this->assertDatabaseHas('lead_allocation', [
            'reset_cap' => 1,
            'max_capacity' => 40,
        ]);
    }

    private function callPrivateMethod(): void
    {
        $command = app(ResetLeadAllocationCounts::class);
        $method = new ReflectionMethod(ResetLeadAllocationCounts::class, 'resetNormalLeadAllocationCapacity');
        $method->invoke($command);
    }
}
