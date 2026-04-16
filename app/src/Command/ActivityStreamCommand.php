<?php

namespace App\Command;

use App\Strava\Activities;
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
class ActivityStreamCommand extends Command
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
            $tempFile = $this->fetch($input, $output);
            $io->success(sprintf('Activity stream dumped to: %s ✅', $tempFile));

            return Command::SUCCESS;
        } catch (\Throwable $ex) {
            $io->error(sprintf('%s', $ex));

            return Command::FAILURE;
        }
    }

    private function fetch(InputInterface $input, OutputInterface $output): string
    {
        $userId = $input->getOption('strava_user_id');
        assert(is_string($userId));

        $activityId = $input->getArgument('strava_activity_id');
        assert(is_string($activityId));

        $activity = $this->activities->fetchStreams(
            intval($userId),
            intval($activityId)
        );

        $varDir = dirname(__DIR__, 2).'/var';
        if (!is_dir($varDir)) {
            throw new \RuntimeException(sprintf('var directory does not exist: %s', $varDir));
        }

        $filename = sprintf('activity_stream_%d_%d_%d.json', $userId, $activityId, time());
        $tempFile = $varDir.'/'.$filename;

        $data = [
            'time' => $activity->time ? [
                'data' => $activity->time->data,
                'series_type' => $activity->time->seriesType,
                'resolution' => $activity->time->resolution,
                'original_size' => $activity->time->originalSize,
            ] : null,
            'distance' => $activity->distance ? [
                'data' => $activity->distance->data,
                'series_type' => $activity->distance->seriesType,
                'resolution' => $activity->distance->resolution,
                'original_size' => $activity->distance->originalSize,
            ] : null,
            'heartrate' => $activity->heartrate ? [
                'data' => $activity->heartrate->data,
                'series_type' => $activity->heartrate->seriesType,
                'resolution' => $activity->heartrate->resolution,
                'original_size' => $activity->heartrate->originalSize,
            ] : null,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode activity stream data to JSON');
        }

        $written = file_put_contents($tempFile, $json);
        if (false === $written) {
            throw new \RuntimeException(sprintf('Failed to write to temporary file: %s', $tempFile));
        }

        return $tempFile;
    }
}
