<?php

namespace App\Command;

use App\AI\RAG\ReportProgressInterface;
use App\Strava\API\Activity;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

final class IngestionProgressAdapter implements ReportProgressInterface
{
    private function __construct(
        private readonly ProgressIndicator $progress,
        private readonly Table $table,
        private int $count = 0,
    ) {
        $progress->start('Ingesting...');
        $table->setHeaders(['ID', 'Date', 'Name', 'Distance', 'Velocity']);
    }

    public static function create(OutputInterface $output): self
    {
        return new self(
            new ProgressIndicator($output),
            new Table($output)
        );
    }

    public function __destruct()
    {
        $this->progress->finish(sprintf('Ingested %d activitie(s)', $this->count));

        if ($this->count) {
            $this->table->render();
        }
    }

    public function progress(Activity $activity): void
    {
        $this->progress->advance();

        $this->table->addRow([
            $activity->getId(),
            $activity->getStartDateLocalTime()->format(\DateTimeImmutable::RFC2822),
            $activity->getName(),
            $activity->getDistance(),
            $activity->getAverageVelocityAsStringable(),
        ]);

        ++$this->count;
    }
}
