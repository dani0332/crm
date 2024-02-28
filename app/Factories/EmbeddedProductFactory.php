<?php

namespace App\Factories;

use App\Enums\EmbeddedProductEnum;
use App\Strategies\MDX;

class EmbeddedProductFactory
{
    public static function createStrategy($shortCode)
    {
        $strategy = null;
        $shortCode = strtoupper($shortCode);
        if ($shortCode == EmbeddedProductEnum::MDX) {
            $strategy = new MDX();
        }

        return $strategy;
    }
}
