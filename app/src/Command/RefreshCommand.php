<?php

namespace App\Command;

use App\Strava\ApiDetails;
use App\Strava\ApiToken;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:refresh',
    description: 'Gain access token by providing a refresh token',
)]
class RefreshCommand extends Command
{
    public function __construct(
        private ApiDetails $apiDetails,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('refresh_token', InputArgument::REQUIRED, 'Refresh token')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $refreshToken = $input->getArgument('refresh_token');

        assert(is_string($refreshToken));
        assert(!empty($refreshToken));

        $accessToken = $this->fetchAccessToken($refreshToken);

        var_dump($accessToken);

        return Command::SUCCESS;
    }

    public function fetchAccessToken(string $refreshToken): ApiToken
    {
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

        return new ApiToken($data);
    }
}
