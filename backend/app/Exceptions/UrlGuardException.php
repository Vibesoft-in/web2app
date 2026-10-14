<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class UrlGuardException extends Exception
{
    protected string $errorCode;

    public function __construct(string $errorCode, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
