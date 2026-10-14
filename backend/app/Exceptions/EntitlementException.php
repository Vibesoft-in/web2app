<?php

namespace App\Exceptions;

use Exception;

class EntitlementException extends Exception
{
    protected string $errorCode;

    public function __construct(string $message, string $errorCode = 'FEATURE_LOCKED', int $statusCode = 403)
    {
        parent::__construct($message, $statusCode);
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function render($request)
    {
        return response()->json([
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
            ],
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 403);
    }
}
