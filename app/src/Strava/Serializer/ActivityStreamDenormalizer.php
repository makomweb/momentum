<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\ActivityStream;
use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class ActivityStreamDenormalizer implements DenormalizerInterface
{
    public function __construct(private SeriesDenormalizer $denormalizer)
    {
    }

    /**
     * @param array<string, array<array{
     *  data: array<int|float>,
     *  seriesType: string,
     *  resolution: string,
     *  originalSize: int
     * }>> $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ActivityStream
    {
        return new ActivityStream(
            time: $this->denormalizeSeries($data, 'time'),
            distance: $this->denormalizeSeries($data, 'distance'),
            velocity: $this->denormalizeSeries($data, 'velocity_smooth', 'velocity'),
            heartrate: $this->denormalizeSeries($data, 'heartrate'),
            watts: $this->denormalizeSeries($data, 'watts'),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return ActivityStream::class === $type && is_array($data);
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [ActivityStream::class => true];
    }

    /**
     * @param array<string, array<array{
     *  data: array<int|float>,
     *  seriesType: string,
     *  resolution: string,
     *  originalSize: int
     * }>> $data
     */
    private function denormalizeSeries(array $data, string $key, string $fallback = ''): Series
    {
        if (isset($data[$key])) {
            $series = $data[$key];
        } elseif (!empty($fallback) && isset($data[$fallback])) {
            $series = $data[$fallback];
        } else {
            $series = [];
        }

        return !empty($series)
            ? $this->denormalizer->denormalize($series, Series::class, null, [])
            : Series::empty($key);
    }
}
