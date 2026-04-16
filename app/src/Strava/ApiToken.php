<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class ApiToken
{
    /**
     * @param array{
     *  token_type: string,
     *  expires_at: int,
     *  expires_in: int,
     *  access_token: string,
     *  refresh_token: string,
     *  athlete: array<string, mixed>
     * } $data
     */
    public function __construct(private array $data)
    {
    }

    public function getTokenType(): ApiTokenType
    {
        return ApiTokenType::from($this->data['token_type']);
    }

    public function getAccessToken(): string
    {
        return $this->data['access_token'];
    }

    public function getRefreshToken(): string
    {
        return $this->data['refresh_token'];
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromTimestamp(
            $this->data['expires_at']
        );
    }

    public function getExpiresIn(): int
    {
        return $this->data['expires_in'];
    }

    public function getAthlete(): Athlete
    {
        return new Athlete($this->data['athlete']);
    }
}
