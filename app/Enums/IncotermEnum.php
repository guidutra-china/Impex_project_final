<?php

namespace App\Enums;

enum IncotermEnum: string
{
    // Any mode of transport
    case EXW = 'EXW';
    case FCA = 'FCA';
    case CPT = 'CPT';
    case CIP = 'CIP';
    case DAP = 'DAP';
    case DPU = 'DPU';
    case DDP = 'DDP';

    // Sea and inland waterway transport
    case FAS = 'FAS';
    case FOB = 'FOB';
    case CFR = 'CFR';
    case CIF = 'CIF';

    public function label(): string
    {
        return match($this) {
            self::EXW => 'EXW - Ex Works',
            self::FCA => 'FCA - Free Carrier',
            self::CPT => 'CPT - Carriage Paid To',
            self::CIP => 'CIP - Carriage and Insurance Paid To',
            self::DAP => 'DAP - Delivered at Place',
            self::DPU => 'DPU - Delivered at Place Unloaded',
            self::DDP => 'DDP - Delivered Duty Paid',
            self::FAS => 'FAS - Free Alongside Ship',
            self::FOB => 'FOB - Free on Board',
            self::CFR => 'CFR - Cost and Freight',
            self::CIF => 'CIF - Cost, Insurance and Freight',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::EXW => 'Seller makes goods available at their premises',
            self::FCA => 'Seller delivers goods to carrier nominated by buyer',
            self::CPT => 'Seller pays freight to destination',
            self::CIP => 'Seller pays freight and insurance to destination',
            self::DAP => 'Seller delivers when goods are placed at buyer disposal',
            self::DPU => 'Seller delivers and unloads at named place',
            self::DDP => 'Seller delivers goods cleared for import',
            self::FAS => 'Seller delivers when goods are alongside vessel',
            self::FOB => 'Seller delivers when goods are on board vessel',
            self::CFR => 'Seller pays freight to destination port',
            self::CIF => 'Seller pays freight and insurance to destination port',
        };
    }

    public function isShippingIncluded(): bool
    {
        return in_array($this, [
            self::CPT, self::CIP, self::DAP, self::DPU, self::DDP,
            self::CFR, self::CIF
        ]);
    }

    public function isInsuranceIncluded(): bool
    {
        return in_array($this, [self::CIP, self::CIF]);
    }
}