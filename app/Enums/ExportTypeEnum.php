<?php

namespace App\Enums;

enum ExportTypeEnum: string
{
    case Download = 'download';
    case Email = 'email';
}
