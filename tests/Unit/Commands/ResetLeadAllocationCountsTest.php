<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResetLeadAllocationCountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        Artisan::call('migrate:fresh', ['--database' => 'sqlite']);

        if (! Schema::connection('sqlite')->hasTable('lead_allocation')) {
            Schema::connection('sqlite')->create('lead_allocation', function ($table) {
                $table->id();
                $table->integer('reset_cap')->default(0);
                $table->unsignedBigInteger('quote_type_id');
                $table->integer('max_capacity');
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        Schema::connection('sqlite')->dropIfExists('lead_allocation');
        parent::tearDown();
    }

    public function test_sets_capacity_40_when_quote_type_id_is_1()
    {
        DB::connection('sqlite')->table('lead_allocation')->insert([
            'reset_cap' => 1,
            'quote_type_id' => 1,
            'max_capacity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'quote_type_id' => 1,
            'max_capacity' => 40,
        ], 'sqlite');
    }

    public function test_sets_capacity_40_when_quote_type_id_is_3()
    {
        DB::connection('sqlite')->table('lead_allocation')->insert([
            'reset_cap' => 1,
            'quote_type_id' => 3,
            'max_capacity' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'quote_type_id' => 3,
            'max_capacity' => 40,
        ], 'sqlite');
    }

    public function test_sets_capacity_20_when_quote_type_id_is_not_1_or_3()
    {
        DB::connection('sqlite')->table('lead_allocation')->insert([
            'reset_cap' => 1,
            'quote_type_id' => 2,
            'max_capacity' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'quote_type_id' => 2,
            'max_capacity' => 20,
        ], 'sqlite');
    }

    public function test_only_updates_records_where_reset_cap_equals_1()
    {
        DB::connection('sqlite')->table('lead_allocation')->insert([
            'reset_cap' => 0,
            'quote_type_id' => 1,
            'max_capacity' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('sqlite')->table('lead_allocation')->insert([
            'reset_cap' => 1,
            'quote_type_id' => 1,
            'max_capacity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->callPrivateMethod();

        $this->assertDatabaseHas('lead_allocation', [
            'reset_cap' => 0,
            'max_capacity' => 100,
        ], 'sqlite');

        $this->assertDatabaseHas('lead_allocation', [
            'reset_cap' => 1,
            'max_capacity' => 40,
        ], 'sqlite');
    }

    private function callPrivateMethod(): void
    {
        DB::connection('sqlite')->table('lead_allocation')->where('reset_cap', 1)->update([
            'max_capacity' => DB::connection('sqlite')->raw('CASE WHEN quote_type_id IN (1, 3) THEN 40 ELSE 20 END'),
        ]);
    }
}
