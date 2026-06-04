<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SeriesNormalizer implements NormalizerInterface
{
    /**
     * @param Series $object
     *
     * @return array<string, mixed>
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'data' => $object->data,
            'series_type' => $object->seriesType,
            'resolution' => $object->resolution,
            'original_size' => $object->originalSize,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Series;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Series::class => true];
    }
}
