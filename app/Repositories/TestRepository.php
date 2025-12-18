<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PestTestTable;

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

    /**
     * Create a PestTestTable record
     *
     * @param string $name
     * @param string $email
     * @return PestTestTable
     */
    public function createPestTestRecord(string $name, string $email): PestTestTable
    {
        return PestTestTable::create([
            'name' => $name,
            'email' => $email,
        ]);
    }

    /**
     * Delete a PestTestTable record by ID
     *
     * @param int $id
     * @return bool
     */
    public function deletePestTestRecord(int $id): bool
    {
        $record = PestTestTable::find($id);
        
        if ($record) {
            return $record->delete();
        }

        return false;
    }
}
