<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentTypeEnum: string implements HasLabel, HasColor
{
    case RECEIPT = 'receipt';
    case PAYMENT = 'payment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RECEIPT => 'Receipt',
            self::PAYMENT => 'Payment',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::RECEIPT => 'success',
            self::PAYMENT => 'danger',
        };
    }
}

