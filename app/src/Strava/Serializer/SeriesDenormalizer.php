<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class SeriesDenormalizer implements DenormalizerInterface
{
    /**
     * @param array{
     *  data: array<int|float>,
     *  seriesType: string,
     *  resolution: string,
     *  originalSize: int
     * }[] $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Series
    {
        /** @var array<int|float> $arrayData */
        $arrayData = $data['data'] ?? [];
        $seriesType = $data['series_type'] ?? '';
        assert(is_string($seriesType));

        $resolution = $data['resolution'] ?? '';
        assert(is_string($resolution));

        $originalSize = $data['original_size'] ?? 0;
        assert(is_int($originalSize));

        return new Series(
            data: $arrayData,
            seriesType: $seriesType,
            resolution: $resolution,
            originalSize: $originalSize,
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
