<?php

declare(strict_types=1);

use Database\Seeders\AwnicNationalitySeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function (): void {
    if (Schema::hasTable('nationality')) {
        Schema::drop('nationality');
    }
});

it('updates matching nationality rows and leaves unmatched seed codes untouched in the database', function (): void {
    Schema::dropIfExists('nationality');

    Schema::create('nationality', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->unsignedInteger('awni_country_code_number')->default(0);
    });

    DB::table('nationality')->insert([
        'code' => 'Afghan',
        'awni_country_code_number' => 0,
    ]);

    (new AwnicNationalitySeeder)->run();

    expect(DB::table('nationality')->where('code', 'Afghan')->value('awni_country_code_number'))->toBe(1001);
});
