<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::Transfer => 'Virement',
        };
    }

    /** Moyens proposés au client à la commande (le virement est réservé à l'enregistrement par l'atelier). */
    public static function forCustomers(): array
    {
        return [self::Cash, self::Wave, self::OrangeMoney];
    }
}
