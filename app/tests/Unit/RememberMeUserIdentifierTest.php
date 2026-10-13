<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\User;
use App\Strava\API\Athlete;
use PHPUnit\Framework\TestCase;

final class RememberMeUserIdentifierTest extends TestCase
{
    private function createAthlete(int $id, string $firstName = 'John', string $lastName = 'Doe'): Athlete
    {
        return Athlete::from([
            'id' => $id,
            'username' => strtolower($firstName.$lastName),
            'firstname' => $firstName,
            'lastname' => $lastName,
            'sex' => 'M',
            'premium' => false,
            'profile_medium' => 'http://example.com/profile-medium.jpg',
            'profile' => 'http://example.com/profile.jpg',
        ]);
    }

    public function test_user_identifier_is_numeric_strava_id(): void
    {
        // Create a test user
        $athlete = $this->createAthlete(12345);
        $user = User::fromAthlete($athlete);

        // The user identifier should be the Strava ID as a string
        $identifier = $user->getUserIdentifier();

        // Verify it's numeric (required for remember-me to work)
        $this->assertTrue(is_numeric($identifier), 'User identifier must be numeric for remember-me cookie validation');
        $this->assertSame('12345', $identifier);
    }

    public function test_user_identifier_matches_strava_id(): void
    {
        // Create a test user
        $athlete = $this->createAthlete(54321, 'Jane', 'Smith');
        $user = User::fromAthlete($athlete);

        // The user identifier should exactly match the Strava ID
        $this->assertSame((string) $user->getStravaId(), $user->getUserIdentifier());
    }

    public function test_user_identifier_remains_numeric_after_creation(): void
    {
        // Create a test user with large Strava ID
        $athlete = $this->createAthlete(987654321);
        $user = User::fromAthlete($athlete);

        // Verify the identifier is numeric
        $identifier = $user->getUserIdentifier();
        $this->assertTrue(is_numeric($identifier));
        $this->assertSame('987654321', $identifier);

        // Verify it can be converted back to int without loss
        $this->assertSame(987654321, (int) $identifier);
    }
}
