<?php

namespace App\Command;

use App\AI\RAG\DocumentFactory;
use App\AI\RAG\MyChatbot;
use App\AI\RAG\NeuronAdapter;
use App\AI\RAG\ReportProgressInterface;
use App\Entity\User;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\Power;
use App\Repository\ActivityRepository;
use App\Repository\UserRepository;
use App\Strava\API\Activity;
use App\Strava\API\ApiException;
use App\Strava\Synchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
        private ActivityRepository $activities,
        private NeuronAdapter $adapter,
        private DocumentFactory $documentFactory,
        private UserRepository $users,
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
            $this->synchronizer->sync($stravaUserId, new ReportProgressAdapter($output, $io));
        } catch (ApiException $ex) {
            $io->error($ex->getMessage());

            return Command::FAILURE;
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error synchronizing: %s', $ex));

            return Command::FAILURE;
        }

        $activities = [];

        try {
            $activities = $this->activities->findByStravaUser($stravaUserId);
            self::showTable($output, $activities);
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error loading: %s', $ex));

            return Command::FAILURE;
        }

        $this->adapter->setStravaUserId($stravaUserId);
        MyChatbot::initialize($this->adapter);

        try {
            MyChatbot::make()
                ->ingest(
                    $this->documentFactory->yieldDocuments(
                        $this->createAthlete($stravaUserId),
                        $activities,
                        $this->createReportAdapter($output)
                    )
                );
        } catch (\Throwable $ex) {
            $io->error(sprintf('Error ingesting: %s', $ex));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function createAthlete(int $stravaUserId): AthleteInterface
    {
        $user = $this->users->findByStravaId($stravaUserId);
        assert($user instanceof User);

        $athlete = new class($user) implements AthleteInterface {
            public function __construct(private User $user)
            {
            }

            public function getId(): int
            {
                return $this->user->getStravaId();
            }

            public function getName(): string
            {
                return $this->user->getName();
            }

            public function getRunTreshold(): Pace
            {
                return $this->user->getRunTreshold();
            }

            public function getCyclingTreshold(): Power
            {
                return $this->user->getCyclingTreshold();
            }

            public function getMaxHeartRate(): HeartRate
            {
                return $this->user->getMaxHeartRate();
            }

            public function getProfilePicture(): string
            {
                return $this->user->getProfilePictureMedium();
            }

            public function isConfigured(): bool
            {
                throw new \Exception('Not implemented');
            }

            public function toArray(): array
            {
                throw new \Exception('Not implemented');
            }
        };

        return $athlete;
    }

    private function createReportAdapter(OutputInterface $output): ReportProgressInterface
    {
        return new class($output) implements ReportProgressInterface {
            public function __construct(private OutputInterface $output)
            {
            }

            public function onActivity(Activity $activity): void
            {
                $this->output->write('.');
            }
        };
    }

    /**
     * @param Activity[] $activities
     */
    private static function showTable(OutputInterface $output, array $activities): void
    {
        if (!empty($activities)) {
            $table = new Table($output);
            $table->setHeaders(['ID', 'Date', 'Name', 'Distance', 'Velocity']);

            foreach ($activities as $activity) {
                $table->addRow([
                    $activity->getId(),
                    $activity->getStartDateLocalTime()->format(\DateTimeImmutable::RFC2822),
                    $activity->getName(),
                    $activity->getDistance(),
                    $activity->getAverageVelocityAsStringable(),
                ]);
            }
            $table->render();
        }
    }
}
