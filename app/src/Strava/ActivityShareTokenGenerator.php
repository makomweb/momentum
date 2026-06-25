<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Assert\Assert;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Generates and validates signed tokens for sharing activities.
 * Tokens are stateless and encode: activity_id + expiry_timestamp.
 * TODO: Rename to ActivityTokenGenerator.
 */
final readonly class ActivityShareTokenGenerator
{
    private const int DEFAULT_EXPIRY_DAYS = 30;

    public function __construct(private string $appSecret)
    {
    }

    /**
     * Generate a shareable token for an activity.
     *
     * @param int $stravaActivityId The database activity ID
     * @param int $expiryDays       Number of days until token expires (default: 30)
     *
     * @return array{
     *  token: string,
     *  expires_at: string,
     *  signature: string,
     * }
     */
    public function generate(int $stravaUserId, int $stravaActivityId, int $expiryDays = self::DEFAULT_EXPIRY_DAYS): array
    {
        $data = self::encode($stravaUserId, $stravaActivityId, $expiryDays);
        $token = $data['token'];
        $expiresAt = $data['expires_at'];

        return [
            'token' => $token,
            'expires_at' => $expiresAt->format(\DateTimeInterface::ATOM),
            'signature' => hash_hmac('sha256', $token, $this->appSecret),
        ];
    }

    /**
     * Validate and extract parts from a token.
     *
     * @return array{
     *  strava_user_id: int,
     *  strava_activity_id: int,
     *  expires_at: int
     * }
     */
    public function validate(string $raw): array
    {
        $parts = explode('.', $raw);

        Assert::that(3 === count($parts), 'Invalid payload format.');

        [$token, $signature, $_] = $parts;

        $expectedSignature = hash_hmac('sha256', $token, $this->appSecret);

        Assert::that(
            hash_equals($expectedSignature, $signature),
            new AccessDeniedHttpException('Invalid or tampered share token.')
        );

        $decoded = self::decode($token);

        Assert::that(
            $decoded['expires_at'] >= time(),
            new AccessDeniedHttpException('This share link has expired.')
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
     *  expires_at: int
     * }
     */
    private static function decode(string $payload): array
    {
        $parts = explode('.', $payload);

        Assert::that(3 === count($parts), 'Invalid payload format.');

        return [
            'strava_user_id' => (int) base_convert($parts[0], 36, 10),
            'strava_activity_id' => (int) base_convert($parts[1], 36, 10),
            'expires_at' => (int) base_convert($parts[2], 36, 10),
        ];
    }
}
