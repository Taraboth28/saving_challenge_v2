<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';

    /**
     * Apply the transaction direction to an amount (deposits add, withdrawals subtract).
     */
    public function signedAmount(float $amount): float
    {
        return $this === self::Deposit ? $amount : -$amount;
    }
}
