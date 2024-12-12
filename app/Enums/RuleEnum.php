<?php

namespace App\Enums;

enum RuleEnum: string
{
    use Enumable;

    case COMMERCIAL_USE = 'Commercial Use';
    case PRIVATE_USE = 'Private Use';


}
