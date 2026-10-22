<?php

namespace App\Command;

use App\Strava\API\Activity;
use App\Strava\DownloadProgressInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class DownloadProgressAdapter implements DownloadProgressInterface
{
    private function __construct(
        private readonly OutputInterface $output,
        private readonly Table $table,
        private int $count = 0,
    ) {
        $this->table->setHeaders(['ID', 'Date', 'Name', 'Distance', 'Velocity']);
    }

    public static function create(OutputInterface $output): self
    {
        return new self($output, new Table($output));
    }

    public function onStart(?Activity $resumeAfter): void
    {
        if ($resumeAfter) {
            $this->output->writeln(sprintf('🚀 Resume after "%s"', $resumeAfter));
        } else {
            $this->output->writeln('🚀 Start synchronizing');
        }
    }

    public function onSkip(Activity $activity): void
    {
    }

    public function onSuccess(Activity $activity): void
    {
        $this->table->addRow([
            $activity->getId(),
            $activity->getStartDateLocalTime()->format(\DateTimeImmutable::RFC2822),
            $activity->getName(),
            $activity->getDistance(),
            $activity->getAverageVelocityAsStringable(),
        ]);

        ++$this->count;
    }

    public function onFinish(): void
    {
        if ($this->count) {
            $this->table->render();
        }

        $this->output->writeln(sprintf('✅ Synchronized %d activitie(s)', $this->count));
    }
}
