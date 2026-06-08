<?php

declare(strict_types=1);

namespace App\Momentum;

use App\Momentum\Assert\Assert;

final readonly class ZoneEntries
{
    private \DateInterval $gapTreshold;
    private \DateTimeImmutable $movingTimeStart;
    private \DateTimeImmutable $movingTimeEnd;
    /** @var ActivityDataPoint[] */
    private array $filteredDataPoints;

    /**
     * @param ActivityDataPoint[]        $dataPoints
     * @param array{0: int, 1: int}|null $movingTimeWindow [startOffset, endOffset] in seconds from first data point, or null to use all
     */
    public function __construct(
        private Pace $treshold,
        private HeartRate $maxHeartRate,
        private array $dataPoints,
        ?array $movingTimeWindow = null,
    ) {
        Assert::that(!$treshold->isZero(), 'Please configure a treshold pace!');
        Assert::that($maxHeartRate->isValid(), 'Please configure a max. heart rate!');

        $this->gapTreshold = \DateInterval::createFromDateString('5 seconds');

        // Set moving time boundaries - if provided, filter data points accordingly
        if (null !== $movingTimeWindow && count($dataPoints) > 0) {
            [$startOffset, $endOffset] = $movingTimeWindow;
            $firstPoint = reset($dataPoints);
            $firstTimestamp = $firstPoint->getTimestamp();

            $this->movingTimeStart = $firstTimestamp->add(
                \DateInterval::createFromDateString("$startOffset seconds")
            );
            $this->movingTimeEnd = $firstTimestamp->add(
                \DateInterval::createFromDateString("$endOffset seconds")
            );

            // Filter data points to only include those within moving time window
            $this->filteredDataPoints = array_values(array_filter(
                $dataPoints,
                fn (ActivityDataPoint $point) => $point->getTimestamp() >= $this->movingTimeStart
                    && $point->getTimestamp() <= $this->movingTimeEnd
            ));
        } else {
            // No moving time window - use full range
            if (count($dataPoints) > 0) {
                $this->movingTimeStart = reset($dataPoints)->getTimestamp();
                $this->movingTimeEnd = end($dataPoints)->getTimestamp();
            } else {
                $this->movingTimeStart = new \DateTimeImmutable();
                $this->movingTimeEnd = new \DateTimeImmutable();
            }
            $this->filteredDataPoints = $dataPoints;
        }
    }

    /** @return PaceZoneEntry[] */
    public function getPaceZoneEntries(): array
    {
        return iterator_to_array($this->yieldPaceZoneEntries());
    }

    /** @return HeartRateZoneEntry[] */
    public function getHeartRateZoneEntries(): array
    {
        /** @var HeartRateZoneEntry[] $result */
        $result = array_reduce(
            iterator_to_array($this->yieldHeartRateEntries()),
            self::reduce(...),
            []
        );

        return $result;
    }

    /** @return \Generator<PaceZoneEntry> */
    private function yieldPaceZoneEntries(): \Generator
    {
        for ($i = 0; $i < count($this->filteredDataPoints); ++$i) {
            $current = $this->filteredDataPoints[$i];
            $entry = PaceZoneEntry::fromDataPoint($current, $this->treshold);

            if (0 == $i) {
                yield $entry;
            } else {
                // Soley append when gap treshold is exceeded OR pace zones vary:
                $previous = $this->filteredDataPoints[$i - 1];

                if (
                    $previous->getTimestamp()->add($this->gapTreshold) < $current->getTimestamp()
                    || $previous->getPaceZone($this->treshold) !== $current->getPaceZone($this->treshold)
                ) {
                    yield $entry;
                }
            }
        }
    }

    /** @return \Generator<HeartRateZoneEntry> */
    private function yieldHeartRateEntries(): \Generator
    {
        for ($i = 0; $i < count($this->filteredDataPoints); ++$i) {
            $current = $this->filteredDataPoints[$i];
            $entry = HeartRateZoneEntry::fromDataPoint($current, $this->maxHeartRate);

            if (0 == $i) {
                yield $entry;
            } else {
                // Soley append when gap treshold is exceeded OR heart rate zones vary:
                $previous = $this->filteredDataPoints[$i - 1];
                if (
                    $previous->getTimestamp()->add($this->gapTreshold) < $current->getTimestamp()
                    || $previous->getHeartRateZone($this->maxHeartRate) !== $current->getHeartRateZone($this->maxHeartRate)
                ) {
                    yield $entry;
                }
            }
        }
    }

    /**
     * @param array<PaceZoneEntry|HeartRateZoneEntry> $carry
     *
     * @return array<PaceZoneEntry|HeartRateZoneEntry>
     */
    private function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry $current): array
    {
        if (empty($carry)) {
            return [$current];
        }

        $previous = array_last($carry);
        assert(get_class($previous) === get_class($current), 'Should be of same type!');

        // Append when gap treshold exceeds:
        if ($current->entered > $previous->entered->add($this->gapTreshold)) {
            return [...$carry, $current];
        }

        // Append when zones vary:
        if ($previous->zone !== $current->zone) {
            return [...$carry, $current];
        }

        // Don't append:
        return $carry;
    }
}
