<?php

namespace App\Domain\Usuario\Enum;

enum DeviceType: string
{
    case WEB = 'WEB';
    case ANDROID = 'ANDROID';
    case IOS = 'IOS';
}
