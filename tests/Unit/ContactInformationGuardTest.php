<?php

declare(strict_types=1);

use App\Domain\Messaging\Services\ContactInformationGuard;

test('message contact guard detects personal emails and phone numbers', function (): void {
    $guard = app(ContactInformationGuard::class);

    expect($guard->containsContactDetails('Please email me at person@example.com'))->toBeTrue()
        ->and($guard->containsContactDetails('Call me at +1 (415) 555-0132'))->toBeTrue()
        ->and($guard->containsContactDetails('I can discuss the project tomorrow'))->toBeFalse();
});
