<?php

namespace App\Command;

use App\Async\Message\SyncActivitiesMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:sync',
    description: 'Sync activities for the specified Strava user (fetches new/updated activities)',
)]
class SyncActivitiesCommand extends Command
{
    public function __construct(private MessageBusInterface $bus)
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

        $stravaUserId = $input->getArgument('strava_user_id');
        assert(is_string($stravaUserId));
        $stravaUserId = (int) $stravaUserId;

        $io->title(sprintf('Syncing activities for Strava user %d', $stravaUserId));

        $message = new SyncActivitiesMessage($stravaUserId);
        $this->bus->dispatch($message);

        $output->writeln('<info>✓ Message dispatched to async queue</info>');
        $output->writeln('  Check worker logs: docker compose logs -f worker');

        return Command::SUCCESS;
    }
}
