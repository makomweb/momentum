<?php

namespace App\Command;

use App\Strava\API\Activity;
use App\Strava\API\MissingTokenException;
use App\Strava\ReportProgressInterface;
use App\Strava\Synchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activities',
    description: 'Sync activities for the specified Strava user (fetches new/updated activities)',
)]
class FetchActivitiesCommand extends Command
{
    public function __construct(
        private Synchronizer $synchronizer,
    ) {
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

        $stravaUserId = $input->getArgument('strava_user_id');
        assert(is_string($stravaUserId));
        $stravaUserId = (int) $stravaUserId;

        $io->info(sprintf('Syncing activities for Strava user %d...', $stravaUserId));

        try {
            $this->synchronizer->sync($stravaUserId, self::createProgressAdapter($io));

            return Command::SUCCESS;
        } catch (MissingTokenException $ex) {
            $io->error(sprintf('%s (Run app:auth first!)', $ex->getMessage()));

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error: %s', $ex));

            return Command::FAILURE;
        }
    }

    private static function createProgressAdapter(SymfonyStyle $io): ReportProgressInterface
    {
        return new class($io) implements ReportProgressInterface {
            public function __construct(
                private readonly SymfonyStyle $io,
                public int $count = 0,
            ) {
            }

            public function onActivity(Activity $activity): void
            {
                $this->io->writeln($activity);
                ++$this->count;
            }

            public function onException(Activity $activity, \Throwable $ex): void
            {
                $this->io->error(sprintf('Error at: %s\\n %s', $activity, $ex));
            }

            public function onFinish(): void
            {
                $this->io->success(sprintf('Synchronized "%d" activities ✅', $this->count));
            }
        };
    }
}
