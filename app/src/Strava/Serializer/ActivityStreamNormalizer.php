<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\ActivityStream;
use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ActivityStreamNormalizer implements NormalizerInterface
{
    /**
     * @param ActivityStream $object
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'time' => $object->time ? $this->normalizeSeries($object->time) : null,
            'distance' => $object->distance ? $this->normalizeSeries($object->distance) : null,
            'velocity' => $object->velocity ? $this->normalizeSeries($object->velocity) : null,
            'heartrate' => $object->heartrate ? $this->normalizeSeries($object->heartrate) : null,
            'watts' => $object->watts ? $this->normalizeSeries($object->watts) : null,
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
        $normalizer = new SeriesNormalizer();

        return $normalizer->normalize($series, null, []);
    }
}
