<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Identifier is the Strava ID (cast from string)
        if (!is_numeric($identifier)) {
            throw new UserNotFoundException(sprintf('User with ID "%s" not found.', $identifier));
        }

        $stravaId = (int) $identifier;
        $user = $this->userRepository->findByStravaId($stravaId);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with Strava ID "%d" not found.', $stravaId));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, got %s instead.', User::class, $user::class));
        }

        // Reload user from database to ensure it's current
        $reloaded = $this->userRepository->findByStravaId($user->getStravaId());

        if (!$reloaded) {
            throw new UserNotFoundException(sprintf('User with Strava ID "%d" not found.', $user->getStravaId()));
        }

        return $reloaded;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}
