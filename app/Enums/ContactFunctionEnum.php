<?php

namespace App\Enums;

enum ContactFunctionEnum: string
{
    case CEO = 'CEO';
    case CTO = 'CTO';
    case CFO = 'CFO';
    case MANAGER = 'Manager';
    case SUPERVISOR = 'Supervisor';
    case ANALYST = 'Analyst';
    case SPECIALIST = 'Specialist';
    case COORDINATOR = 'Coordinator';
    case DIRECTOR = 'Director';
    case CONSULTANT = 'Consultant';
    case SALES = 'Sales';
    case SALES_MANAGER = 'Sales Manager';
    case OTHERS = 'Others';

    /**
     * Get all enum values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all enum cases as key-value pairs for dropdowns
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->value;
        }
        return $options;
    }

    /**
     * Get the label for display
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Get color for badges (optional, for UI enhancement)
     */
    public function color(): string
    {
        return match($this) {
            self::CEO, self::CTO, self::CFO => 'danger',
            self::DIRECTOR => 'warning',
            self::MANAGER, self::SALES_MANAGER => 'success',
            self::SUPERVISOR, self::COORDINATOR => 'info',
            self::SALES => 'primary',
            default => 'gray',
        };
    }

    /**
     * Check if this is an executive role
     */
    public function isExecutive(): bool
    {
        return in_array($this, [self::CEO, self::CTO, self::CFO]);
    }

    /**
     * Check if this is a management role
     */
    public function isManagement(): bool
    {
        return in_array($this, [
            self::CEO, self::CTO, self::CFO,
            self::DIRECTOR, self::MANAGER, self::SALES_MANAGER
        ]);
    }
}
