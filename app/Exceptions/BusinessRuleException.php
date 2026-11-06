<?php

namespace App\Exceptions;

use RuntimeException;

/** Règle métier non respectée. Le message est en français et affichable tel quel ; `field` désigne le champ concerné. */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'general')
    {
        parent::__construct($message);
    }
}
