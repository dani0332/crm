<?php

namespace App\Enums;

enum DatabaseConnectionEnum: string
{
    case MYSQL = 'mysql';
    case MYSQL_READ = 'mysql_read';
}
