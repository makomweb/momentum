<?php

namespace App\Command;

use App\AI\RAG\MyChatbot;
use App\Strava\API\ApiException;
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
        private MyChatbot $chatbot,
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

        $io->title(sprintf('Syncing activities for Strava user %d', $stravaUserId));

        try {
            $this->synchronizer->sync($stravaUserId, DownloadProgressAdapter::create($output));
        } catch (ApiException $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error synchronizing: %s', $ex));

            return Command::FAILURE;
        }

        try {
            $this->chatbot->ingest($stravaUserId, IngestionProgressAdapter::create($output));
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error ingesting: %s', $ex));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
