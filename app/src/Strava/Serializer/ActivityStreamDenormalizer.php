<?php

declare(strict_types=1);

namespace App\Strava\Serializer;

use App\Strava\API\ActivityStream;
use App\Strava\API\Series;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ActivityStreamDenormalizer implements DenormalizerInterface
{
    public function __construct(private SeriesDenormalizer $seriesDenormalizer)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ActivityStream
    {
        /**
         * @var array{
         *  data: array<int|float>,
         *  seriesType: string,
         *  resolution: string,
         *  originalSize: int
         * }[] $timeSeries */
        $timeSeries = isset($data['time']) ? $data['time'] : [];

        /**
         * @var array{
         *  data: array<int|float>,
         *  seriesType: string,
         *  resolution: string,
         *  originalSize: int
         * }[] $distanceSeries */
        $distanceSeries = isset($data['distance']) ? $data['distance'] : [];

        /**
         * @var array{
         *  data: array<int|float>,
         *  seriesType: string,
         *  resolution: string,
         *  originalSize: int
         * }[] $velocitySeries */
        $velocitySeries = isset($data['velocity_smooth']) ? $data['velocity_smooth'] : (isset($data['velocity']) ? $data['velocity'] : []);

        /**
         * @var array{
         *  data: array<int|float>,
         *  seriesType: string,
         *  resolution: string,
         *  originalSize: int
         * }[] $heartrateSeries */
        $heartrateSeries = isset($data['heartrate']) ? $data['heartrate'] : [];

        /**
         * @var array{
         *  data: array<int|float>,
         *  seriesType: string,
         *  resolution: string,
         *  originalSize: int
         * }[] $wattsSeries */
        $wattsSeries = isset($data['watts']) ? $data['watts'] : [];

        return new ActivityStream(
            time: $this->denormalizeSeries($timeSeries),
            distance: $this->denormalizeSeries($distanceSeries),
            velocity: $this->denormalizeSeries($velocitySeries),
            heartrate: $this->denormalizeSeries($heartrateSeries),
            watts: $this->denormalizeSeries($wattsSeries),
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
     * @param array{
     *  data: array<int|float>,
     *  seriesType: string,
     *  resolution: string,
     *  originalSize: int
     * }[] $data
     */
    private function denormalizeSeries(array $data): ?Series
    {
        return empty($data)
            ? null
            : $this->seriesDenormalizer->denormalize($data, Series::class, null, []);
    }
}
