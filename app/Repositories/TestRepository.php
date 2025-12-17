<?php

namespace App\Repositories;

class TestRepository
{
    public function generateUsername(string $name): string {
        return strtolower(str_replace(' ', '_', $name));
    }

    public function checkAge(int $age): bool {
        return $age > 18;
    }
	
	public function isPaymentExists($code){
		return $code === 'CAR-ABC123';
	}
}
