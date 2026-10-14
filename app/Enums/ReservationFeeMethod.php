<?php

namespace App\Enums;

enum ReservationFeeMethod: string
{
    case Gcash = 'gcash';
    case Walkin = 'walkin';

    public function label(): string
    {
        return match ($this) {
            self::Gcash => 'GCash',
            self::Walkin => 'Walk-in (Pay at Counter)',
        };
    }
}
