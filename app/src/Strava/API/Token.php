<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class Token
{
    public function __construct(
        public TokenType $tokenType,
        public \DateTimeImmutable $expiresAt,
        public int $expiresInSeconds,
        public string $accessToken,
        public string $refreshToken,
        public Athlete $athlete,
    ) {
    }

    /**
     * @return array{
     *  token_type: string,
     *  expires_at: int,
     *  expires_in: int,
     *  access_token: string,
     *  refresh_token: string,
     *  athlete: array{
     *   id: int,
     *   username: string,
     *   firstname: string,
     *   lastname: string,
     *   sex: string,
     *   premium: bool,
     *   profile_medium: string,
     *   profile: string
     *  }
     * }
     */
    public function toArray(): array
    {
        return [
            'token_type' => $this->tokenType->value,
            'expires_at' => $this->expiresAt->getTimestamp(),
            'expires_in' => $this->expiresInSeconds,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'athlete' => $this->athlete->toArray(),
        ];
    }

    /**
     * @param array{
     *  token_type: string,
     *  expires_at: int,
     *  expires_in: int,
     *  access_token: string,
     *  refresh_token: string,
     *  athlete: array{
     *   id: int,
     *   username: string,
     *   firstname: string,
     *   lastname: string,
     *   sex: string,
     *   premium: bool,
     *   profile_medium: string,
     *   profile: string
     *  }
     * } $data
     */
    public static function fromData(array $data): self
    {
        return new self(
            TokenType::from($data['token_type']),
            \DateTimeImmutable::createFromTimestamp($data['expires_at']),
            $data['expires_in'],
            $data['access_token'],
            $data['refresh_token'],
            Athlete::fromData($data['athlete'])
        );
    }

    /**
     * @param array{
     *  token_type: string,
     *  expires_at: int,
     *  expires_in: int,
     *  access_token: string,
     *  refresh_token: string
     * } $data
     */
    public function withData(array $data): self
    {
        return self::fromData([
            ...$this->toArray(),
            ...$data,
        ]);
    }

    public function getAuthHeaderValue(): string
    {
        return sprintf(
            '%s %s',
            $this->tokenType->value,
            $this->accessToken
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
