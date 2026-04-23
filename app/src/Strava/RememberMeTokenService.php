<?php

declare(strict_types=1);

namespace App\Strava;

use App\Entity\RememberMeToken;
use App\Entity\User;
use App\Repository\RememberMeTokenRepository;

final readonly class RememberMeTokenService
{
    public function __construct(
        private RememberMeTokenRepository $tokenRepository,
        private int $lifetime,
    ) {
    }

    /**
     * Create a new remember-me token pair (selector + validator).
     * Stores selector and hashed validator in database.
     * Returns both values to set in cookie.
     *
     * @return array{selector: string, validator: string}
     */
    public function createToken(User $user): array
    {
        // Clean up expired tokens first
        $this->tokenRepository->deleteExpired();

        // Generate selector and validator
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $hashedValidator = hash('sha256', $validator);

        // Store in database
        $expiresAt = new \DateTimeImmutable(sprintf('+%d seconds', $this->lifetime));
        $token = RememberMeToken::create($selector, $hashedValidator, $user, $expiresAt);
        $this->tokenRepository->save($token, true);

        return [
            'selector' => $selector,
            'validator' => $validator,
        ];
    }

    /**
     * Validate a remember-me token pair.
     * If valid and not expired, returns the user.
     * Returns null if invalid, expired, or not found.
     */
    public function validateToken(string $selector, string $validator): ?User
    {
        $token = $this->tokenRepository->findBySelector($selector);
        if (!$token) {
            return null;
        }

        if ($token->isExpired()) {
            $this->tokenRepository->delete($token, true);

            return null;
        }

        $hashedValidator = hash('sha256', $validator);
        if (!hash_equals($token->getHashedValidator(), $hashedValidator)) {
            // Token tampering or invalid validator - delete it
            $this->tokenRepository->delete($token, true);

            return null;
        }

        return $token->getUser();
    }

    /**
     * Delete all remember-me tokens for a user (on logout).
     */
    public function deleteTokensForUser(User $user): void
    {
        $this->tokenRepository->deleteByUser($user);
    }

    /**
     * Get the cookie name for remember-me.
     */
    public static function getCookieName(): string
    {
        return 'REMEMBERME';
    }

    /**
     * Format token values into cookie content.
     *
     * @param array{selector: string, validator: string} $tokenPair
     */
    public static function formatCookieValue(array $tokenPair): string
    {
        return sprintf('%s:%s', $tokenPair['selector'], $tokenPair['validator']);
    }

    /**
     * Parse cookie content back into token values.
     *
     * @return array{selector: string, validator: string}|null
     */
    public static function parseCookieValue(string $cookieValue): ?array
    {
        $parts = explode(':', $cookieValue, 2);
        if (2 !== count($parts)) {
            return null;
        }

        return [
            'selector' => $parts[0],
            'validator' => $parts[1],
        ];
    }
}
