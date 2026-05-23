<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Exceptions;

use Exception;

class InvalidPatternException extends Exception
{
    public function __construct(string $message = 'Invalid pattern format', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
