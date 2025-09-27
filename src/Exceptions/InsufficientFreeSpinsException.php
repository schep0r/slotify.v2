<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when user tries to use free spins but has none available.
 */
class InsufficientFreeSpinsException extends \Exception
{
    public function __construct(string $message = 'Insufficient free spins available', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
