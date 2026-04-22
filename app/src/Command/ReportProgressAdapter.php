<?php

namespace App\Command;

use App\Strava\API\Activity;
use App\Strava\ReportProgressInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ReportProgressAdapter implements ReportProgressInterface
{
    private Table $table;

    public function __construct(
        OutputInterface $output,
        private readonly SymfonyStyle $io,
        public int $count = 0,
    ) {
        $this->table = new Table($output);
        $this->table->setHeaders(['ID', 'Date', 'Name', 'Distance', 'Avg. Speed']);
    }

    public function reportStart(?Activity $resumeAfter): void
    {
        if ($resumeAfter) {
            $this->io->info(sprintf('🚀 Resume after "%s"', $resumeAfter));
        } else {
            $this->io->info('🚀 Start synchronizing');
        }
    }

    public function skip(Activity $activity): void
    {
    }

    public function report(Activity $activity): void
    {
        $this->table->addRow([
            $activity->getId(),
            $activity->getStartDateLocalTime()->format(\DateTimeImmutable::RFC2822),
            $activity->getName(),
            $activity->getDistance(),
            $activity->getAverageSpeed(),
        ]);

        ++$this->count;
    }

    public function reportFinish(): void
    {
        $this->table->render();

        $this->io->success(sprintf('Synchronized %d activitie(s) ✅', $this->count));
    }
}
