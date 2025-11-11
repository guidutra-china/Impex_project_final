<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountTypeEnum: string implements HasLabel, HasColor
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ASSET => 'Asset',
            self::LIABILITY => 'Liability',
            self::EQUITY => 'Equity',
            self::REVENUE => 'Revenue',
            self::EXPENSE => 'Expense',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::ASSET => 'success',
            self::LIABILITY => 'danger',
            self::EQUITY => 'info',
            self::REVENUE => 'primary',
            self::EXPENSE => 'warning',
        };
    }
}

