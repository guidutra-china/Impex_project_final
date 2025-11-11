<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EntryTypeEnum: string implements HasLabel, HasColor
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DEBIT => 'Debit',
            self::CREDIT => 'Credit',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::DEBIT => 'success',
            self::CREDIT => 'danger',
        };
    }
}

