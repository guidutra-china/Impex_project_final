<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BankAccountTypeEnum: string implements HasLabel
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
    case CREDIT = 'credit';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CHECKING => 'Checking Account',
            self::SAVINGS => 'Savings Account',
            self::CREDIT => 'Credit Account',
        };
    }
}

