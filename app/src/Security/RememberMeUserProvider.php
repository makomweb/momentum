<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Strava\RememberMeTokenService;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads users from remember-me tokens stored in the database.
 * Integrates with Symfony's built-in remember-me system.
 *
 * @implements UserProviderInterface<User>
 */
final readonly class RememberMeUserProvider implements UserProviderInterface
{
    public function __construct(
        private RememberMeTokenService $tokenService,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Load a user by remember-me token identifier.
     * The identifier is in format: selector:validator.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $tokenPair = RememberMeTokenService::parseCookieValue($identifier);
        if (!$tokenPair) {
            throw new UserNotFoundException('Invalid remember-me token format');
        }

        $user = $this->tokenService->validateToken($tokenPair['selector'], $tokenPair['validator']);
        if (!$user) {
            throw new UserNotFoundException('Remember-me token is invalid or expired');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s', User::class, $user::class));
        }

        $refreshed = $this->userRepository->findByStravaId($user->getStravaId());
        if (!$refreshed) {
            throw new UserNotFoundException('User no longer exists');
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
