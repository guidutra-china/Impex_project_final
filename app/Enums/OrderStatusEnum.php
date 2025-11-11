<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatusEnum: string implements HasLabel, HasColor
{
    case NEW = 'New';
    case CONFIRMED = 'Confirmed';
    case CANCELLED = 'Cancelled';
    case WAITING_PAYMENT = 'Waiting Payment';
    case PAID = 'Paid';
    case WAITING_PRODUCTION = 'Waiting Production';
    case READY_TO_SHIP = 'Ready to Ship';
    case SHIPPED = 'Shipped';
    case REFUNDED = 'Refunded';
    case FINALIZED = 'Finalized';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::NEW => 'primary',
            self::CONFIRMED => 'success',
            self::CANCELLED => 'danger',
            self::WAITING_PAYMENT => 'warning',
            self::PAID => 'primary',
            self::WAITING_PRODUCTION => 'info',
            self::READY_TO_SHIP => 'warning',
            self::SHIPPED => 'primary',
            self::REFUNDED => 'success',
            self::FINALIZED => 'success',
        };
    }

}
