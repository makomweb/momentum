<?php

declare(strict_types=1);

namespace App\Strava\Service;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Generates and validates signed tokens for sharing activities.
 * Tokens are stateless and encode: activity_id + expiry_timestamp.
 */
final class ActivityShareTokenGenerator
{
    private const int DEFAULT_EXPIRY_DAYS = 30;

    public function __construct(private readonly string $appSecret)
    {
    }

    /**
     * Generate a shareable token for an activity.
     *
     * @param int $activityId The database activity ID
     * @param int $expiryDays Number of days until token expires (default: 30)
     *
     * @return array{token: string, expiresAt: \DateTimeImmutable}
     */
    public function generate(int $activityId, int $expiryDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        $now = new \DateTimeImmutable('UTC');
        $expiresAt = $now->add(\DateInterval::createFromDateString("{$expiryDays} days"));

        $payload = $this->encode_payload($activityId, $expiresAt->getTimestamp());
        $signature = $this->sign_payload($payload);
        $token = "{$payload}.".$signature;

        return [
            'token' => $token,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * Validate and extract activity ID from a token.
     *
     * @param string $token The share token
     *
     * @return int The activity ID if valid
     *
     * @throws AccessDeniedHttpException If token is invalid or expired
     */
    public function validate(string $token): int
    {
        $parts = explode('.', $token);
        if (2 !== count($parts)) {
            throw new AccessDeniedHttpException('Invalid share token format.');
        }

        [$payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = $this->sign_payload($payload);
        if (!hash_equals($expectedSignature, $signature)) {
            throw new AccessDeniedHttpException('Invalid or tampered share token.');
        }

        // Decode payload and verify expiry
        [$activityId, $expiryTimestamp] = $this->decode_payload($payload);

        if ($expiryTimestamp < time()) {
            throw new AccessDeniedHttpException('This share link has expired.');
        }

        return $activityId;
    }

    private function encode_payload(int $activityId, int $expiryTimestamp): string
    {
        // Create a compact encoding: activityId:expiryTimestamp (base36)
        return base_convert((string) $activityId, 10, 36).':'.base_convert((string) $expiryTimestamp, 10, 36);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function decode_payload(string $payload): array
    {
        $parts = explode(':', $payload);
        if (2 !== count($parts)) {
            throw new AccessDeniedHttpException('Invalid payload format.');
        }

        $activityId = (int) base_convert($parts[0], 36, 10);
        $expiryTimestamp = (int) base_convert($parts[1], 36, 10);

        return [$activityId, $expiryTimestamp];
    }

    private function sign_payload(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->appSecret);
    }
}
