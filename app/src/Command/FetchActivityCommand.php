<?php

namespace App\Command;

use App\Strava\Activities;
use App\Strava\StravaException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activity',
    description: 'Fetch details about a specific activity',
)]
class FetchActivityCommand extends Command
{
    public function __construct(private Activities $activities)
    {
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
            $filePath = $this->fetchActivity($input);
            $io->success(sprintf('Activity stream dumped to: %s ✅', $filePath));

            return Command::SUCCESS;
        } catch (StravaException $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('%s', $ex));

            return Command::FAILURE;
        }
    }

    private function fetchActivity(InputInterface $input): string
    {
        $userId = $input->getOption('strava_user_id');
        assert(is_string($userId));

        $activityId = $input->getArgument('strava_activity_id');
        assert(is_string($activityId));

        $varDir = dirname(__DIR__, 2).'/var';
        if (!is_dir($varDir)) {
            throw new \RuntimeException(sprintf('var directory does not exist: %s', $varDir));
        }

        $filePath = $this->activities->dumpActivity(
            intval($userId),
            intval($activityId),
            $varDir
        );

        return $filePath;
    }
}
