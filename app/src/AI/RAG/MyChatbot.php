<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\Entity\User;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\Power;
use App\Repository\UserRepository;
use App\Strava\ActivityRepositoryInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;

class MyChatbot extends RAG
{
    public function __construct(
        private NeuronAdapterInterface $adapter,
        private DocumentFactoryInterface $documentFactory,
        private UserRepository $users,
        private ActivityRepositoryInterface $activities,
    ) {
        parent::__construct();
    }

    public function setStravaUserId(int $stravaUserId): void
    {
        $this->adapter->setStravaUserId($stravaUserId);
    }

    public function ingest(int $stravaUserId, ReportProgressInterface $progress): void
    {
        $this->adapter->setStravaUserId($stravaUserId);

        $activities = $this->activities->findByStravaUser($stravaUserId);

        $this->addDocuments(
            iterator_to_array(
                $this->documentFactory->yieldDocuments(
                    $this->createAthlete($stravaUserId),
                    $activities,
                    $progress
                )
            )
        );
    }

    protected function provider(): AIProviderInterface
    {
        return $this->adapter->createAIProvider();
    }

    protected function embeddings(): EmbeddingsProviderInterface
    {
        return $this->adapter->createEmbeddingsProvider();
    }

    protected function vectorStore(): VectorStoreInterface
    {
        return $this->adapter->createVectorStore();
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
}
