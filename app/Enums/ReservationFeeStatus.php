<?php

namespace App\Enums;

enum ReservationFeeStatus: string
{
    case Unpaid = 'unpaid';
    case AwaitingVerification = 'awaiting_verification';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::AwaitingVerification => 'Awaiting Verification',
            self::Paid => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'secondary',
            self::AwaitingVerification => 'warning',
            self::Paid => 'success',
        };
    }
}
