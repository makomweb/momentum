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
            'times' => $object->times ? $this->normalizeSeries($object->times) : null,
            'distances' => $object->distances ? $this->normalizeSeries($object->distances) : null,
            'velocity' => $object->velocities ? $this->normalizeSeries($object->velocities) : null,
            'heartrates' => $object->heartrates ? $this->normalizeSeries($object->heartrates) : null,
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
