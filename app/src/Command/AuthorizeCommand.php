<?php

namespace App\Command;

use App\Strava\API\Client;
use App\Strava\API\Configuration;
use App\Strava\API\Token;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auth',
    description: 'Authorize a Strava user (authentication happens offside)',
)]
class AuthorizeCommand extends Command
{
    public function __construct(
        private Configuration $config,
        private Client $client,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $token = $this->authorize($input, $output);
            $io->success(sprintf('"%s" authorized ✅', $token->getAthlete()));

            return Command::SUCCESS;
        } catch (\Throwable $ex) {
            $io->error(sprintf('%s', $ex));

            return Command::FAILURE;
        }
    }

    private function authorize(InputInterface $input, OutputInterface $output): Token
    {
        $address = $this->config->getAuthorizationAddress();

        $output->writeln('Open this URL in your browser:');
        $output->writeln($address);

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        $question = new Question('Paste the "code" parameter here: ');
        $code = $helper->ask($input, $output, $question);
        assert(is_string($code));

        return $this->client->requestToken($code);
    }
}
