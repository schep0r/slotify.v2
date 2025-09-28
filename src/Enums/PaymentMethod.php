<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case PAYPAL = 'paypal';
    case DUMMY = 'dummy';

    public function getLabel(): string
    {
        return match ($this) {
            self::CARD => 'Credit/Debit Card',
            self::PAYPAL => 'PayPal',
            self::DUMMY => 'Dummy Payment (Dev)',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CARD => '💳',
            self::PAYPAL => '🅿️',
            self::DUMMY => '🔧',
        };
    }
}
