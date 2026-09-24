<?php

namespace App\Services\Security;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class SecurityEventLogger
{
    /**
     * Security logging must never prevent a customer or employee from signing in.
     * Identifiers are keyed hashes so the log remains useful without retaining PII.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Request $request,
        string $event,
        ?User $user = null,
        ?string $identifier = null,
        array $metadata = [],
    ): void {
        try {
            SecurityEvent::query()->create([
                'user_id' => $user?->getKey(),
                'event' => $event,
                'identifier_hash' => $identifier === null ? null : hash_hmac(
                    'sha256',
                    mb_strtolower(trim($identifier)),
                    (string) config('app.key'),
                ),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500) ?: null,
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
