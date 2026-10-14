<?php

namespace App\Exceptions;

use Exception;

class BuildException extends Exception
{
    public function __construct(
        protected string $errorCode,
        string $message = '',
        protected int $statusCode = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
