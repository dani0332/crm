<?php

// test/Unit/TestRepositoryTest.php

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

test('check if record is created', function(){
    $repository = new TestRepository();
    
    // Define test data
    $name = 'Test User';
    $email = 'test@example.com';
    
    // Create record using repository
    $pest = $repository->createPestTestRecord($name, $email);

    // Assert record exists in database
    $this->assertDatabaseHas('pest_test_table', [
        'email' => $email,
        'name' => $name
    ]);

    // Assert returned record has correct data
    expect($pest->name)->toBe($name)
        ->and($pest->email)->toBe($email)
        ->and($pest->id)->toBeInt();
});

// Asserting Record Does Not Exist
test('is deleted from database', function () {
    $repository = new TestRepository();
    
    // Define test data
    $name = 'Test User';
    $email = 'test@example.com';
    
    // Create record using repository
    $pest = $repository->createPestTestRecord($name, $email);
    $pestId = $pest->id;

    // Delete the record using repository
    $repository->deletePestTestRecord($pestId);

    // Assert record does not exist in database
    $this->assertDatabaseMissing('pest_test_table', [
        'id' => $pestId,
    ]);
});

// // 6. API Assertions in Laravel Pest
// test('can list users', function () {
//     // Create 3 users
//     User::factory()->count(3)->create();
//     $response = $this->getJson('/api/users');
//     $response
//         ->assertStatus(200)
//         ->assertJsonCount(3, 'data');
// });