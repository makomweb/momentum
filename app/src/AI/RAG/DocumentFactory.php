<?php

declare(strict_types=1);

namespace App\AI\RAG;

use App\Momentum\AthleteInterface;
use App\Strava\ActivityData;
use App\Strava\ActivityStreamCache;
use App\Strava\API\Activity;
use App\Twig\FormatMovingTime;
use App\Twig\TrainingMetricsFilter;
use NeuronAI\RAG\Document;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DocumentFactory
{
    public function __construct(
        private TranslatorInterface $translator,
        private ActivityStreamCache $cache,
        private TrainingMetricsFilter $metricsFilter,
    ) {
    }

    /**
     * @param Activity[] $activities
     *
     * @return \Generator<Document>
     */
    public function yieldDocuments(AthleteInterface $athlete, array $activities, ReportProgressInterface $report): \Generator
    {
        foreach ($activities as $activity) {
            $metrics = $this->getActivityData($athlete, $activity, $report)->toArray()['metrics'];

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
                    '{easy_percentage}' => round($metrics->getEasyPercentage(), 0),
                    '{medium_percentage}' => round($metrics->getMediumPercentage(), 0),
                    '{hard_percentage}' => round($metrics->getHardPercentage(), 0),
                    '{suggestion}' => $this->metricsFilter->getDescription($metrics),
                ],
            );

            yield new Document($content)
                ->setSourceType(get_class($activity))
                ->setSourceName($activity->getName())
                ->setMetadata([
                    'strava_user_id' => $athlete->getId(),
                    'activity_id' => $activity->getId(),
                    ...array_diff_key($activity->data, array_flip(['id', 'elapsed_time', 'moving_time'])),
                    'easy_percentage' => round($metrics->getEasyPercentage(), 0),
                    'medium_percentage' => round($metrics->getMediumPercentage(), 0),
                    'hard_percentage' => round($metrics->getHardPercentage(), 0),
                    'suggestion' => $this->metricsFilter->getDescription($metrics),
                    'duration' => FormatMovingTime::format($activity->getElapsedTime())
                ]);
        }
    }

    private function getActivityData(AthleteInterface $athlete, Activity $activity, ReportProgressInterface $report): ActivityData
    {
        $stream = $this->cache->get($athlete->getId(), $activity->getId());

        $report->progress();

        return new ActivityData($athlete, $activity, $stream);
    }
}
