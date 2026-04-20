<?php

namespace App\Command;

use App\Strava\API\Activity;
use App\Strava\API\MissingTokenException;
use App\Strava\Application\ReportProgressInterface;
use App\Strava\Application\SyncStravaActivities;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activities',
    description: 'Sync activities for the specified Strava user (fetches new/updated activities)',
)]
class FetchActivitiesCommand extends Command
{
    public function __construct(
        private SyncStravaActivities $syncService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('strava_user_id', InputArgument::REQUIRED, 'Strava user ID')
            ->addOption('force-reset', 'r', InputOption::VALUE_NONE, 'Force full re-sync (clears last sync state)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stravaUserId = $input->getArgument('strava_user_id');
        assert(is_string($stravaUserId));
        $stravaUserId = (int) $stravaUserId;

        if ($input->getOption('force-reset')) {
            $io->info('Forcing full re-sync (clearing sync state)...');
            $this->syncService->resetSync($stravaUserId);
        }

        $io->info(sprintf('Syncing activities for Strava user %d...', $stravaUserId));

        try {
            $result = $this->syncService->sync(
                $stravaUserId,
                new class($io) implements ReportProgressInterface {
                    public function __construct(private readonly SymfonyStyle $io)
                    {
                    }

                    public function report(Activity $activity): void
                    {
                        $this->io->writeln($activity);
                    }

                    public function reportException(Activity $activity, \Throwable $ex): void
                    {
                        $this->io->error(sprintf('Error at: %s\\n %s', $activity, $ex));
                    }
                }
            );
            $this->displayResults($io, $result);

            return Command::SUCCESS;
        } catch (MissingTokenException $ex) {
            $io->error(sprintf('%s (Run app:auth first!)', $ex->getMessage()));

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error: %s', $ex->getMessage()));

            return Command::FAILURE;
        }
    }

    /**
     * @param array{
     *   synced_count: int,
     *   total_pages: int,
     *   last_activity_timestamp: int|null
     * } $result
     */
    private function displayResults(SymfonyStyle $io, array $result): void
    {
        $table = new Table($io);
        $table->setHeaders(['Metric', 'Value']);
        $table->addRows([
            ['Activities Synced', $result['synced_count']],
            ['Pages Processed', $result['total_pages']],
            ['Last Activity Timestamp', $result['last_activity_timestamp'] ? date('Y-m-d H:i:s', $result['last_activity_timestamp']) : 'N/A'],
        ]);
        $table->render();

        $io->success(sprintf(
            'Sync completed! Synced %d activities from %d pages ✅',
            $result['synced_count'],
            $result['total_pages']
        ));
    }
}
