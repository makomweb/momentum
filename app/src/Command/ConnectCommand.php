<?php

namespace App\Command;

use App\Strava\ApiDetails;
use App\Strava\ApiToken;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:connect',
    description: 'Generate connect URL',
)]
class ConnectCommand extends Command
{
    public function __construct(
        private ApiDetails $apiDetails,
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $redirectUri = 'http://localhost/exchange-token';

        $url = sprintf(
            'https://www.strava.com/oauth/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=activity:read_all',
            $this->apiDetails->clientId,
            urlencode($redirectUri)
        );

        $output->writeln('Open this URL in your browser:');
        $output->writeln($url);

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        $question = new Question('Paste the "code" parameter here: ');
        $code = $helper->ask($input, $output, $question);
        assert(is_string($code));

        $token = $this->fetchToken($code);

        var_dump($token);

        return Command::SUCCESS;
    }

    private function fetchToken(string $code): ApiToken
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

        return new ApiToken($data);
    }
}
