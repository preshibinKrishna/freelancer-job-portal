<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingTransactionStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
