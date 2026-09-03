<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Reserved = 'reserved';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Vacant',
            self::Occupied => 'Occupied',
            self::Reserved => 'Reserved',
            self::Maintenance => 'Maintenance',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Occupied => 'danger',
            self::Reserved => 'warning',
            self::Maintenance => 'secondary',
        };
    }
}
