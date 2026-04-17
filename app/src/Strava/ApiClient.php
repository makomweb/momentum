<?php

declare(strict_types=1);

namespace App\Strava;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ApiClient
{
    public function __construct(
        private ApiDetails $apiDetails,
        private HttpClientInterface $httpClient,
        private ApiTokenCache $cache,
        private string $redirectUri = 'http://localhost/exchange-token',
    ) {
    }

    public function getInitAddress(): string
    {
        return sprintf(
            'https://www.strava.com/oauth/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=activity:read_all',
            $this->apiDetails->clientId,
            urlencode($this->redirectUri)
        );
    }

    public function fetchRefreshToken(string $code): ApiToken
    {
        $response = $this->httpClient->request('POST', 'https://www.strava.com/oauth/token', [
            'body' => [
                'client_id' => $this->apiDetails->clientId,
                'client_secret' => $this->apiDetails->clientSecret,
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

        $token = new ApiToken($data);

        $this->cache->saveToken($token);

        return $token;
    }

    public function fetchAccessToken(int $stravaUserId): ApiToken
    {
        $token = $this->cache->getToken($stravaUserId);

        return $this->doFetchAccessToken($token);
    }

    private function doFetchAccessToken(ApiToken $token): ApiToken
    {
        Assert::that(!$token->isExpired(), 'Token is expired!');

        $refreshToken = $token->getRefreshToken();

        assert(!empty($refreshToken));

        $response = $this->httpClient->request('POST', 'https://www.strava.com/oauth/token', [
            'body' => [
                'client_id' => $this->apiDetails->clientId,
                'client_secret' => $this->apiDetails->clientSecret,
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

        $accessToken = new ApiToken($data);

        $this->cache->saveToken($token->withAccessToken($accessToken));

        return $accessToken;
    }
}
