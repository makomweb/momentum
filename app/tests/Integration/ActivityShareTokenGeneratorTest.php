<?php

namespace App\Tests\Integration;

use App\Strava\Service\ActivityShareTokenGenerator;
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
        $result = $this->generator->generate(activityId: 123);

        self::assertArrayHasKey('token', $result);
        self::assertArrayHasKey('expiresAt', $result);
        self::assertInstanceOf(\DateTimeImmutable::class, $result['expiresAt']);
    }

    #[Test]
    public function token_format_is_valid(): void
    {
        $result = $this->generator->generate(activityId: 123);

        // Token should have format: payload.signature
        $parts = explode('.', $result['token']);
        self::assertCount(2, $parts); // payload.signature
    }

    #[Test]
    public function generated_token_validates_successfully(): void
    {
        $result = $this->generator->generate(activityId: 456);

        $activityId = $this->generator->validate($result['token']);

        self::assertEquals(456, $activityId);
    }

    #[Test]
    public function expiry_defaults_to_30_days(): void
    {
        $now = new \DateTimeImmutable('UTC');
        $result = $this->generator->generate(activityId: 789);

        $expectedExpiry = $now->add(\DateInterval::createFromDateString('30 days'));

        // Compare dates (allow 1 second tolerance for execution time)
        $diff = abs($result['expiresAt']->getTimestamp() - $expectedExpiry->getTimestamp());
        self::assertLessThan(2, $diff);
    }

    #[Test]
    public function respects_custom_expiry_days(): void
    {
        $now = new \DateTimeImmutable('UTC');
        $result = $this->generator->generate(activityId: 789, expiryDays: 7);

        $expectedExpiry = $now->add(\DateInterval::createFromDateString('7 days'));

        // Compare dates (allow 1 second tolerance for execution time)
        $diff = abs($result['expiresAt']->getTimestamp() - $expectedExpiry->getTimestamp());
        self::assertLessThan(2, $diff);
    }

    #[Test]
    public function rejects_invalid_token_format(): void
    {
        self::expectException(AccessDeniedHttpException::class);
        self::expectExceptionMessage('Invalid share token format.');

        $this->generator->validate('invalid-token-no-colon');
    }

    #[Test]
    public function rejects_tampered_token(): void
    {
        $result = $this->generator->generate(activityId: 123);
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
        // Create a token that expires immediately (0 days)
        $result = $this->generator->generate(activityId: 123, expiryDays: 0);

        // Wait a tiny bit to ensure expiry time has passed
        sleep(1);

        self::expectException(AccessDeniedHttpException::class);
        self::expectExceptionMessage('This share link has expired.');

        $this->generator->validate($result['token']);
    }

    #[Test]
    public function tokens_with_different_secrets_are_incompatible(): void
    {
        $generator1 = new ActivityShareTokenGenerator('secret-key-1');
        $generator2 = new ActivityShareTokenGenerator('secret-key-2');

        $result = $generator1->generate(activityId: 123);

        self::expectException(AccessDeniedHttpException::class);
        self::expectExceptionMessage('Invalid or tampered share token.');

        $generator2->validate($result['token']);
    }

    #[Test]
    public function same_activity_generates_different_tokens(): void
    {
        $token1 = $this->generator->generate(activityId: 123)['token'];
        // Sleep 1 second to ensure different timestamp
        sleep(1);
        $token2 = $this->generator->generate(activityId: 123)['token'];

        // Tokens should be different (different expiry timestamps)
        self::assertNotEquals($token1, $token2);
    }

    #[Test]
    public function validates_multiple_tokens_independently(): void
    {
        $result1 = $this->generator->generate(activityId: 111);
        $result2 = $this->generator->generate(activityId: 222);

        $activityId1 = $this->generator->validate($result1['token']);
        $activityId2 = $this->generator->validate($result2['token']);

        self::assertEquals(111, $activityId1);
        self::assertEquals(222, $activityId2);
    }

    #[Test]
    public function preserves_activity_id_through_encoding(): void
    {
        $testIds = [1, 42, 999, 12345, 999999];

        foreach ($testIds as $id) {
            $result = $this->generator->generate(activityId: $id);
            $decodedId = $this->generator->validate($result['token']);

            self::assertEquals($id, $decodedId, "Activity ID $id was not preserved");
        }
    }

    #[Test]
    public function rejects_empty_token(): void
    {
        self::expectException(AccessDeniedHttpException::class);

        $this->generator->validate('');
    }

    #[Test]
    public function rejects_malformed_payload(): void
    {
        self::expectException(AccessDeniedHttpException::class);

        // Token with only signature, no payload
        $this->generator->validate('.abcdef1234567890');
    }
}
