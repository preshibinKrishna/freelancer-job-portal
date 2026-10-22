<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingPaymentAttemptStatus: string
{
    case Created = 'created';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
