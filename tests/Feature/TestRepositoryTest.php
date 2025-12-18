<?php

// test/Unit/TestRepositoryTest.php

use App\Models\User;
use App\Repositories\TestRepository;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

// Set up database schema for all HTTP/Feature tests

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->user = TestDataSeeder::createAdminUser();
    $this->actingAs($this->user);
});

// 3. Writing Unit Tests with Pest
test('generates correct username', function () {
    $repository = new TestRepository();

    expect($repository->generateUsername('John Doe'))
        ->toBe('john_doe');
});

test('check age must be greater than 18', function () {
    $repository = new TestRepository();

    expect($repository->checkAge(19))
        ->toBeTrue();

    expect($repository->checkAge(17))
        ->toBeFalse();
});

test("check if payment exists or not", function(){
	$testRepository = new TestRepository(); 
	expect($testRepository->isPaymentExists('CAR-ABC123'))->toBeTrue();
});


// 4. HTTP Status Code Assertions
test('check login access', function(){
    $response = $this->get('/login-page');
    $response->assertOk(); // Checks for 200 status

    $response = $this->get('/non-existent-page');
    $response->assertNotFound(); // Checks for 404 status
});

// 5. Database Assertions in Laravel Pest
