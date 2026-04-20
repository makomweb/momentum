<?php

namespace App\Command;

use App\Strava\API\ActivityDownloader;
use App\Strava\API\MissingTokenException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:activities',
    description: 'Fetch activitites for the specified Strava user',
)]
class FetchCommand extends Command
{
    public function __construct(
        private ActivityDownloader $downloader,
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

        try {
            $this->fetch($input, $output);
            $io->success('Done ✅');

            return Command::SUCCESS;
        } catch (MissingTokenException $ex) {
            $io->error(sprintf('%s (Run app:auth first!)', $ex->getMessage()));

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('%s', $ex));

            return Command::FAILURE;
        }
    }

    private function fetch(InputInterface $input, OutputInterface $output): void
    {
        $stravaUserId = $input->getArgument('strava_user_id');
        assert(is_string($stravaUserId));

        $activities = $this->downloader->fetchActivities(intval($stravaUserId));

        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Start', 'Bewegungszeit', 'Verstr. Zeit', 'Distanz', 'Avg. Speed', 'Max Speed']);

        foreach ($activities as $activity) {
            $table->addRow([
                $activity->getId(),
                $activity->getName(),
                $activity->getStartTime()->format(\DateTimeInterface::RFC2822),
                self::asString($activity->getMovingTime()),
                self::asString($activity->getElapsedTime()),
                $activity->getDistance(),
                $activity->getAverageSpeed(),
                $activity->getMaxSpeed(),
            ]);
        }

        $table->render();
    }

    private static function asString(\DateInterval $interval): string
    {
        $seconds = $interval->s;
        $date = new \DateTimeImmutable('today midnight');

        return $date
            ->modify("+$seconds seconds")
            ->format('H:i:s');
    }
}
