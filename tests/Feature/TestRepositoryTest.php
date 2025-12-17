<!-- https://medium.com/@zulfikarditya/mastering-testing-in-laravel-with-pest-php-a-comprehensive-guide-0d1a599f79f5 -->
<?php

// test/Unit/TestRepositoryTest.php

use App\Repositories\TestRepository;

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