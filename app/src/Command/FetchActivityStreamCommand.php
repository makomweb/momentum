<?php

namespace App\Command;

use App\Momentum\ActivityStream;
use App\Strava\ActivityStreamCache;
use App\Strava\API\ApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stream',
    description: 'Fetch stream about a specific activity',
)]
class FetchActivityStreamCommand extends Command
{
    public function __construct(
        private ActivityStreamCache $cache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('strava_activity_id', InputArgument::REQUIRED, 'Strava activity ID')
            ->addOption('strava_user_id', 'u', InputOption::VALUE_REQUIRED, 'Strava user ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $stream = $this->fetchStream($input);
            $stream->hasData()
                ? $io->success('Activity stream has data ✅')
                : $io->success('Activity stream does not have data ❌');

            return Command::SUCCESS;
        } catch (ApiException $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('%s', $ex));

            return Command::FAILURE;
        }
    }

    private function fetchStream(InputInterface $input): ActivityStream
    {
        $stravaUserId = $input->getOption('strava_user_id');
        assert(is_string($stravaUserId));

        $stravaActivityId = $input->getArgument('strava_activity_id');
        assert(is_string($stravaActivityId));

        return $this->cache->get(intval($stravaUserId), intval($stravaActivityId));
    }
}
