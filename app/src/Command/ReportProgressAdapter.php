<?php

namespace App\Command;

use App\Strava\API\Activity;
use App\Strava\ReportProgressInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ReportProgressAdapter implements ReportProgressInterface
{
    public function __construct(
        private readonly SymfonyStyle $io,
        public int $count = 0,
    ) {
    }

    public function reportStart(?Activity $resumeAfter): void
    {
        if ($resumeAfter) {
            $this->io->info(sprintf('🚀 Resume after "%s"', $resumeAfter));
        } else {
            $this->io->info('🚀 Start synchronizing');
        }
    }

    public function skip(Activity $current): void
    {
        $this->io->writeln(sprintf('Skip: %s', $current));
    }

    public function report(Activity $current): void
    {
        $this->io->writeln($current);
        ++$this->count;
    }

    public function reportFinish(): void
    {
        $this->io->success(sprintf('Synchronized %d activitie(s) ✅', $this->count));
    }
}
