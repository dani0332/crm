<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function (): void {
    if (Schema::hasTable('nationality')) {
        Schema::drop('nationality');
    }
});

it('backfills null or empty awni_country_code_number then enforces not null', function (): void {
    Schema::create('nationality', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->unsignedInteger('awni_country_code_number')->nullable();
    });

    DB::table('nationality')->insert([
        ['code' => 'NeedsBackfill', 'awni_country_code_number' => null],
        ['code' => 'KeepsValue', 'awni_country_code_number' => 4242],
    ]);

    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_05_07_170827_make_awni_country_code_number_not_nullable_on_nationality_table.php');
    $migration->up();

    expect(DB::table('nationality')->where('code', 'NeedsBackfill')->value('awni_country_code_number'))->toBe(0)
        ->and(DB::table('nationality')->where('code', 'KeepsValue')->value('awni_country_code_number'))->toBe(4242);
});
