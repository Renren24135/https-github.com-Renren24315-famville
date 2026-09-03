<?php

namespace App\Enums;

enum BookingSource: string
{
    case Online = 'online';
    case WalkIn = 'walkin';

    public function label(): string
    {
        return $this === self::Online ? 'Online' : 'Walk-In';
    }
}
