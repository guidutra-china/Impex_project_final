<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethodEnum: string implements HasLabel
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case CHECK = 'check';
    case PIX = 'pix';
    case WIRE_TRANSFER = 'wire_transfer';
    case OTHER = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::CHECK => 'Check',
            self::PIX => 'PIX',
            self::WIRE_TRANSFER => 'Wire Transfer',
            self::OTHER => 'Other',
        };
    }
}

