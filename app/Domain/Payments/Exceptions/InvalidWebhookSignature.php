<?php

declare(strict_types=1);

namespace App\Domain\Payments\Exceptions;

use RuntimeException;

class InvalidWebhookSignature extends RuntimeException {}
