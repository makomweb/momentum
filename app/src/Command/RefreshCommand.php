<?php

namespace App\Command;

use App\Strava\API\Token;
use App\Strava\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:refresh',
    description: 'Obtain a fresh token by providing the previous token',
)]
class RefreshCommand extends Command
{
    public function __construct(private Client $client)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('strava_user_id', InputArgument::REQUIRED, 'Strava user ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $token = $this->refresh($input);
            $io->success(sprintf('Token for "%s" refreshed ✅', $token->getAthlete()));

            return Command::SUCCESS;
        } catch (\Throwable $ex) {
            $io->error(sprintf('%s', $ex));

            return Command::FAILURE;
        }
    }

    private function refresh(InputInterface $input): Token
    {
        $stravaUserId = $input->getArgument('strava_user_id');
        assert(is_string($stravaUserId));

        return $this->client->refreshTokenFor(intval($stravaUserId));
    }
}
