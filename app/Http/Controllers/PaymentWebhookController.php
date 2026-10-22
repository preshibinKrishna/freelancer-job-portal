<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Payments\Actions\AcceptVerifiedWebhook;
use App\Domain\Payments\Exceptions\InvalidWebhookSignature;
use App\Domain\Payments\Services\PaymentProviderRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentWebhookController
{
    public function __invoke(Request $request, string $provider, PaymentProviderRegistry $providers, AcceptVerifiedWebhook $accept): JsonResponse
    {
        try {
            $adapter = $providers->resolve($provider);
        } catch (InvalidArgumentException) {
            return response()->json(['status' => 'unavailable'], 503);
        }

        $rawBody = $request->getContent();
        abort_if(strlen($rawBody) > 262144, 413);
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = implode(',', $values);
        }

        try {
            $event = $adapter->verifyAndNormalizeWebhook($headers, $rawBody);
        } catch (InvalidWebhookSignature) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $stored = $accept->execute($event);

        return response()->json(['status' => $stored->status, 'event_id' => $stored->public_id], 202);
    }
}
