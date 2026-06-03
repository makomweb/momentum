<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\ActivityStream;
use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ActivityStreamDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ActivityStream
    {
        /** @var array<string, mixed> $data */
        $time = isset($data['time']) ? $data['time'] : null;
        $distance = isset($data['distance']) ? $data['distance'] : null;
        $velocity = isset($data['velocity_smooth']) ? $data['velocity_smooth'] : (isset($data['velocity']) ? $data['velocity'] : null);
        $heartrate = isset($data['heartrate']) ? $data['heartrate'] : null;

        return new ActivityStream(
            time: $time ? $this->denormalizeSeries((array) $time) : null,
            distance: $distance ? $this->denormalizeSeries((array) $distance) : null,
            velocity: $velocity ? $this->denormalizeSeries((array) $velocity) : null,
            heartrate: $heartrate ? $this->denormalizeSeries((array) $heartrate) : null,
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
     * @param array<string, mixed> $seriesData
     */
    private function denormalizeSeries(array $seriesData): Series
    {
        $denormalizer = new SeriesDenormalizer();

        return $denormalizer->denormalize($seriesData, Series::class, null, []);
    }
}
