<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Occupied = 'occupied';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case VoidPending = 'void_pending';

    public function label(): string
    {
        return match ($this) {
            self::VoidPending => 'Void Pending',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Occupied => 'danger',
            self::Completed => 'success',
            self::Cancelled => 'secondary',
            self::VoidPending => 'dark',
        };
    }
}
