<?php

namespace App\Tests\Integration;

use App\Strava\ActivityShareTokenGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ActivityShareTokenGeneratorTest extends TestCase
{
    private ActivityShareTokenGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ActivityShareTokenGenerator('test-secret-key-for-testing');
    }

    #[Test]
    public function generates_valid_token(): void
    {
        $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456);

        self::assertArrayHasKey('token', $result);
        self::assertArrayHasKey('expires_at', $result);
    }

    #[Test]
    public function token_format_is_valid(): void
    {
        $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456);

        // Token should have format: userId.activityId.expiresAt.signature
        $parts = explode('.', $result['token']);
        self::assertCount(4, $parts);
    }

    #[Test]
    public function generated_token_validates_successfully(): void
    {
        $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456);

        $decoded = $this->generator->validate($result['token']);

        self::assertEquals(456, $decoded['strava_activity_id']);
    }

    #[Test]
    public function expiry_defaults_to_30_days(): void
    {
        $now = new \DateTimeImmutable('UTC');
        $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456);

        $expectedExpiry = $now->add(\DateInterval::createFromDateString('30 days'));
        $actualExpiry = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $result['expires_at']);
        assert($actualExpiry instanceof \DateTimeImmutable);

        // Compare dates (allow 1 second tolerance for execution time)
        $diff = abs($actualExpiry->getTimestamp() - $expectedExpiry->getTimestamp());
        self::assertLessThan(2, $diff);
    }

    #[Test]
    public function respects_custom_expiry_days(): void
    {
        $now = new \DateTimeImmutable('UTC');
        $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: 789, expiryDays: 7);

        $expectedExpiry = $now->add(\DateInterval::createFromDateString('7 days'));
        $actualExpiry = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $result['expires_at']);
        assert($actualExpiry instanceof \DateTimeImmutable);

        // Compare dates (allow 1 second tolerance for execution time)
        $diff = abs($actualExpiry->getTimestamp() - $expectedExpiry->getTimestamp());
        self::assertLessThan(2, $diff);
    }

    #[Test]
    public function rejects_invalid_token_format(): void
    {
        self::expectException(AccessDeniedHttpException::class);

        $this->generator->validate('invalid-token-no-dot');
    }

    #[Test]
    public function rejects_tampered_token(): void
    {
        $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456);
        $token = $result['token'];

        // Tamper with the token by changing part of it
        $parts = explode('.', $token);
        $parts[0] = 'tampered';
        $tamperedToken = implode('.', $parts);

        self::expectException(AccessDeniedHttpException::class);
        self::expectExceptionMessage('Invalid or tampered share token.');

        $this->generator->validate($tamperedToken);
    }

    #[Test]
    public function rejects_expired_token(): void
    {
        // Create a token that's already expired by encoding an expiry timestamp in the past
        $now = new \DateTimeImmutable('UTC');
        $past = $now->sub(\DateInterval::createFromDateString('1 day'));

        // Manually craft an expired token by encoding the past timestamp
        $payload = sprintf(
            '%s.%s.%s',
            base_convert('123', 10, 36),
            base_convert('456', 10, 36),
            base_convert((string) $past->getTimestamp(), 10, 36)
        );

        // Create signature
        $signature = hash_hmac('sha256', $payload, 'test-secret-key-for-testing');
        $fullToken = "{$payload}.{$signature}";

        self::expectException(AccessDeniedHttpException::class);
        self::expectExceptionMessage('This share link has expired.');

        $this->generator->validate($fullToken);
    }

    #[Test]
    public function tokens_with_different_secrets_are_incompatible(): void
    {
        $generator1 = new ActivityShareTokenGenerator('secret-key-1');
        $generator2 = new ActivityShareTokenGenerator('secret-key-2');

        $result = $generator1->generate(stravaUserId: 123, stravaActivityId: 456);

        self::expectException(AccessDeniedHttpException::class);
        self::expectExceptionMessage('Invalid or tampered share token.');

        $generator2->validate($result['token']);
    }

    #[Test]
    public function same_activity_generates_different_tokens(): void
    {
        $token1 = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456)['token'];
        // Sleep 1 second to ensure different timestamp
        sleep(1);
        $token2 = $this->generator->generate(stravaUserId: 123, stravaActivityId: 456)['token'];

        // Tokens should be different (different expiry timestamps)
        self::assertNotEquals($token1, $token2);
    }

    #[Test]
    public function validates_multiple_tokens_independently(): void
    {
        $result1 = $this->generator->generate(stravaUserId: 123, stravaActivityId: 111);
        $result2 = $this->generator->generate(stravaUserId: 123, stravaActivityId: 222);

        $decoded1 = $this->generator->validate($result1['token']);
        $decoded2 = $this->generator->validate($result2['token']);

        self::assertEquals(111, $decoded1['strava_activity_id']);
        self::assertEquals(222, $decoded2['strava_activity_id']);
    }

    #[Test]
    public function preserves_activity_id_through_encoding(): void
    {
        $testIds = [1, 42, 999, 12345, 999999];

        foreach ($testIds as $id) {
            $result = $this->generator->generate(stravaUserId: 123, stravaActivityId: $id);
            $decoded = $this->generator->validate($result['token']);

            self::assertEquals($id, $decoded['strava_activity_id'], "Activity ID $id was not preserved");
        }
    }

    #[Test]
    public function rejects_empty_token(): void
    {
        self::expectException(AccessDeniedHttpException::class);

        $this->generator->validate('....');
    }

    #[Test]
    public function rejects_malformed_payload(): void
    {
        self::expectException(AccessDeniedHttpException::class);

        // Token with wrong number of parts
        $this->generator->validate('a.b.c');
    }
}
