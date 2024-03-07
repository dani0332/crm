<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UserStatusEnum extends Enum
{
    public const ONLINE = 1;
    public const OFFLINE = 2;
    public const UNAVAILABLE = 3;
    public const SICK = 4;
    public const LEAVE = 5;
    public const MANUAL_OFFLINE = 6;
    const UserStatusList = [
        self::ONLINE,
        self::OFFLINE,
        self::UNAVAILABLE,
        self::SICK,
        self::LEAVE,
        self::MANUAL_OFFLINE,
    ];

    public static function getUserStatusText($status)
    {
        $statusText = '';
        switch ($status) {
            case 1:
                $statusText = 'Online';
                break;
            case 2:
                $statusText = 'Offline';
                break;
            case 3:
                $statusText = 'Unavailable';
                break;
            case 4:
                $statusText = 'Sick';
                break;
            case 5:
                $statusText = 'on Leave';
                break;
            case 6:
                $statusText = 'Manual Offline';
                break;
            default:
                break;
        }

        return $statusText;
    }
}
