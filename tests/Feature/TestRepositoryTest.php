<?php

// test/Unit/TestRepositoryTest.php

use App\Repositories\TestRepository;

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