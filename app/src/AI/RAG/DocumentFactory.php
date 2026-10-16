<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\Strava\API\Activity;
use App\Twig\FormatMovingTime;
use NeuronAI\RAG\Document;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DocumentFactory
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param Activity[] $activities
     *
     * @return \Generator<Document>
     */
    public function yieldDocuments(int $stravaUserId, iterable $activities): \Generator
    {
        foreach ($activities as $activity) {
            $content = $this->translator->trans(
                'activity.document_content',
                [
                    '{name}' => $activity->getName(),
                    '{date}' => $activity->getStartDateLocalTime()->format('Y-m-d H:i'),
                    '{type}' => $activity->data['type'],
                    '{distance}' => (string) $activity->getDistance(),
                    '{avg_speed}' => (string) $activity->getAverageVelocityAsStringable(),
                    '{max_speed}' => (string) $activity->getMaxVelocityAsStringable(),
                    '{elapsed_time}' => FormatMovingTime::format($activity->getElapsedTime()),
                    '{device}' => $activity->getDeviceName(),
                ],
            );

            yield new Document($content)
                ->setSourceType(get_class($activity))
                ->setSourceName($activity->getName())
                ->setMetadata([
                    'strava_user_id' => $stravaUserId,
                    'activity_id' => $activity->getId(),
                    ...array_diff_key($activity->data, array_flip(['id'])),
                ]);
        }
    }
}
