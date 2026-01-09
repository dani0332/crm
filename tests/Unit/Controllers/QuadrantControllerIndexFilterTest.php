<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\Quadrant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuadrantControllerIndexFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        Schema::connection('sqlite')->create('quadrants', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('sqlite')->dropIfExists('quadrants');
        parent::tearDown();
    }

    public function test_query_filters_by_quadrants_name_column()
    {
        DB::connection('sqlite')->table('quadrants')->insert([
            ['name' => 'Sales Quadrant', 'code' => 'SQ', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Marketing Team', 'code' => 'MT', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Support Quadrant', 'code' => 'SQ2', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $query = Quadrant::on('sqlite')->orderBy('id');
        $query->where('quadrants.name', 'LIKE', '%Quadrant%');
        $results = $query->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Sales Quadrant', $results[0]->name);
        $this->assertEquals('Support Quadrant', $results[1]->name);
    }

    public function test_query_filters_with_partial_match()
    {
        DB::connection('sqlite')->table('quadrants')->insert([
            ['name' => 'Alpha Team', 'code' => 'AT', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Beta Squad', 'code' => 'BS', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gamma Alpha', 'code' => 'GA', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $query = Quadrant::on('sqlite')->orderBy('id');
        $query->where('quadrants.name', 'LIKE', '%Alpha%');
        $results = $query->get();

        $this->assertCount(2, $results);
    }

    public function test_query_returns_empty_when_no_match()
    {
        DB::connection('sqlite')->table('quadrants')->insert([
            ['name' => 'Team One', 'code' => 'T1', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Team Two', 'code' => 'T2', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $query = Quadrant::on('sqlite')->orderBy('id');
        $query->where('quadrants.name', 'LIKE', '%NonExistent%');
        $results = $query->get();

        $this->assertCount(0, $results);
    }

    public function test_query_uses_table_prefixed_column_name()
    {
        DB::connection('sqlite')->table('quadrants')->insert([
            ['name' => 'Test Quadrant', 'code' => 'TQ', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $query = Quadrant::on('sqlite')->orderBy('id');
        $query->where('quadrants.name', 'LIKE', '%Test%');

        $sql = $query->toSql();

        $this->assertStringContainsString('"quadrants"."name"', $sql);
    }
}
