<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\Assert\Assert;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Client
{
    public function __construct(
        private Configuration $config,
        private HttpClientInterface $httpClient,
        private TokenCacheInterface $cache,
    ) {
    }

    /** Request a token (OAuth 2.0: exchange code with token) */
    public function requestToken(string $code): Token
    {
        $response = $this->httpClient->request('POST', 'https://www.strava.com/oauth/token', [
            'body' => [
                'client_id' => $this->config->clientId,
                'client_secret' => $this->config->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ],
        ]);

        /** @var array{
         *  token_type: string,
         *  expires_at: int,
         *  expires_in: int,
         *  access_token: string,
         *  refresh_token: string,
         *  athlete: array{id: int, username: string, firstname: string, lastname: string, sex: string, premium: bool, profile_medium: string, profile: string}
         * } $data
         */
        $data = $response->toArray();

        $token = new Token($data);

        $this->cache->saveToken($token);

        return $token;
    }

    public function refreshTokenFor(int $stravaUserId): Token
    {
        $token = $this->cache->getToken($stravaUserId);

        return $token->isExpired()
            ? $this->refreshToken($token)
            : $token;
    }

    /**
     * Obtain a fresh token by provding the initial token
     * (OAuth 2.0: refresh token).
     */
    private function refreshToken(Token $token): Token
    {
        $refreshToken = $token->getRefreshToken();

        Assert::that(!empty($refreshToken), 'No refresh token available!');

        $response = $this->httpClient->request('POST', 'https://www.strava.com/oauth/token', [
            'body' => [
                'client_id' => $this->config->clientId,
                'client_secret' => $this->config->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ],
        ]);

        /** @var array{
         *  token_type: string,
         *  expires_at: int,
         *  expires_in: int,
         *  access_token: string,
         *  refresh_token: string,
         *  athlete: array{id: int, username: string, firstname: string, lastname: string, sex: string, premium: bool, profile_medium: string, profile: string}
         * } $data
         */
        $data = $response->toArray();

        $token = $token->withData($data);

        $this->cache->saveToken($token);

        return $token;
    }
}
