<?php

declare(strict_types=1);

namespace App\Enums;

enum MyAlfredWelcomeEmailProcessResult: string
{
    use Enumable;

    case Dispatched = 'dispatched';

    case CustomerNotFound = 'customer_not_found';
}
