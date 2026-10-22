<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Services;

class ContactInformationGuard
{
    public function containsContactDetails(string $text): bool
    {
        return preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $text) === 1
            || preg_match('/(?<!\w)(?:\+?\d[\d().\s-]{5,}\d)(?!\w)/', $text) === 1;
    }
}
