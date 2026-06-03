<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SeriesDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Series
    {
        /** @var array<string, mixed> $data */
        $arrayData = $data['data'] ?? [];
        $seriesType = $data['series_type'] ?? '';
        $resolution = $data['resolution'] ?? '';
        $originalSize = $data['original_size'] ?? 0;

        return new Series(
            data: (array) $arrayData,
            seriesType: (string) $seriesType,
            resolution: (string) $resolution,
            originalSize: (int) $originalSize,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Series::class === $type && is_array($data);
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Series::class => true];
    }
}
