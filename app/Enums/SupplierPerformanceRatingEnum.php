<?php

namespace App\Enums;

enum SupplierPerformanceRatingEnum: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case AVERAGE = 'average';
    case POOR = 'poor';
    case UNACCEPTABLE = 'unacceptable';

    public function label(): string
    {
        return match($this) {
            self::EXCELLENT => 'Excellent',
            self::GOOD => 'Good',
            self::AVERAGE => 'Average',
            self::POOR => 'Poor',
            self::UNACCEPTABLE => 'Unacceptable',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::EXCELLENT => 'success',
            self::GOOD => 'info',
            self::AVERAGE => 'warning',
            self::POOR => 'danger',
            self::UNACCEPTABLE => 'danger',
        };
    }

    public static function fromScore(float $score): self
    {
        return match(true) {
            $score >= 90 => self::EXCELLENT,
            $score >= 75 => self::GOOD,
            $score >= 60 => self::AVERAGE,
            $score >= 40 => self::POOR,
            default => self::UNACCEPTABLE,
        };
    }
}