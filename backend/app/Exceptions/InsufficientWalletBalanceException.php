<?php

namespace App\Exceptions;

use Exception;

class InsufficientWalletBalanceException extends Exception
{
    public function __construct(string $message = 'Insufficient WhatsApp wallet balance. Please recharge your wallet.', int $code = 402)
    {
        parent::__construct($message, $code);
    }

    public function render($request)
    {
        return response()->json([
            'error' => [
                'code' => 'INSUFFICIENT_WALLET_BALANCE',
                'message' => $this->getMessage(),
            ],
            'error_code' => 'INSUFFICIENT_WALLET_BALANCE',
            'message' => $this->getMessage(),
        ], 402);
    }
}
