<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Momentum\ActivityStream;
use App\Momentum\Series;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class ActivityStreamNormalizer implements NormalizerInterface
{
    public function __construct(private SeriesNormalizer $normalizer)
    {
    }

    /**
     * @param ActivityStream $object
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'time' => $this->normalizeSeries($object->time),
            'distance' => $this->normalizeSeries($object->distance),
            'velocity' => $this->normalizeSeries($object->velocity),
            'heartrate' => $this->normalizeSeries($object->heartrate),
            'watts' => $this->normalizeSeries($object->watts),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ActivityStream;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [ActivityStream::class => true];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSeries(Series $series): array
    {
        return $this->normalizer->normalize($series, null, []);
    }
}
