<?php

declare(strict_types=1);

namespace App\Momentum;

use App\Momentum\Assert\Assert;

/**
 * Generates and validates signed tokens for sharing activities.
 * Tokens are stateless and encode: strava_user_id + strava_activity_id + expiry_timestamp.
 */
final readonly class ActivityShareToken
{
    private const int DEFAULT_EXPIRY_DAYS = 30;

    public function __construct(private string $appSecret)
    {
    }

    /**
     * Generate a shareable token for an activity.
     *
     * @return array{
     *  token: string,
     *  expires_at: string,
     * }
     */
    public function generate(int $stravaUserId, int $stravaActivityId, int $expiryDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        $data = self::encode($stravaUserId, $stravaActivityId, $expiryDays);
        $payload = $data['token'];
        $expiresAt = $data['expires_at'];

        $signature = hash_hmac('sha256', $payload, $this->appSecret);
        $token = "{$payload}.{$signature}";

        return [
            'token' => $token,
            'expires_at' => $expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Validate and extract parts from a token.
     *
     * @return array{
     *  strava_user_id: int,
     *  strava_activity_id: int,
     *  expires_at: \DateTimeImmutable
     * }
     */
    public function validate(string $raw): array
    {
        $parts = explode('.', $raw);

        Assert::that(4 === count($parts), new ActivityShareTokenException('Invalid share token format.'));

        [$userIdPart, $activityIdPart, $expiryPart, $signature] = $parts;
        $payload = "{$userIdPart}.{$activityIdPart}.{$expiryPart}";

        $expectedSignature = hash_hmac('sha256', $payload, $this->appSecret);

        Assert::that(
            hash_equals($expectedSignature, $signature),
            new ActivityShareTokenException('Invalid or tampered share token.')
        );

        $decoded = self::decode($payload);

        Assert::that(
            $decoded['expires_at']->getTimestamp() >= time(),
            new ActivityShareTokenException('This share link has expired.')
        );

        return $decoded;
    }

    /**
     * @return array{
     *  token: string,
     *  expires_at: \DateTimeImmutable
     * }
     */
    private static function encode(int $stravaUserId, int $stravaActivityId, int $expiryDays): array
    {
        $now = new \DateTimeImmutable('UTC');
        $expiresAt = $now->add(\DateInterval::createFromDateString("{$expiryDays} days"));

        $token = sprintf(
            '%s.%s.%s',
            base_convert((string) $stravaUserId, 10, 36),
            base_convert((string) $stravaActivityId, 10, 36),
            base_convert((string) $expiresAt->getTimestamp(), 10, 36)
        );

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * @return array{
     *  strava_user_id: int,
     *  strava_activity_id: int,
     *  expires_at: \DateTimeImmutable
     * }
     */
    private static function decode(string $payload): array
    {
        $parts = explode('.', $payload);

        Assert::that(3 === count($parts), 'Invalid payload format.');

        return [
            'strava_user_id' => (int) base_convert($parts[0], 36, 10),
            'strava_activity_id' => (int) base_convert($parts[1], 36, 10),
            'expires_at' => \DateTimeImmutable::createFromTimestamp((int) base_convert($parts[2], 36, 10)),
        ];
    }
}
