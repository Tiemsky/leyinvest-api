<?php

namespace App\Enums;

enum PlanEnum: string
{
    case GRATUIT = 'gratuit';
    case PRO = 'pro';
    case PREMIUM = 'premium';

    /**
     * Get all role values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role label.
     */
    public function label(): string
    {
        return match ($this) {
            self::GRATUIT => 'gratuit',
            self::PRO => 'pro',
            self::PREMIUM => 'premium',
        };
    }
}
