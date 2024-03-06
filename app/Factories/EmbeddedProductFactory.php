<?php

namespace App\Factories;

use App\Strategies\EmbeddedProducts\MDX;

class EmbeddedProductFactory
{
    public static function createStrategy($shortCode)
    {
        $strategy = null;
        $shortCode = strtoupper($shortCode);
        if ($shortCode == 'MDX') {
            $strategy = new MDX();
        }

        return $strategy;
    }
}
