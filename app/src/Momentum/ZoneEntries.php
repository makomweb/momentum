<?php

declare(strict_types=1);

namespace App\Momentum;

use App\Momentum\Assert\Assert;

final readonly class ZoneEntries
{
    /** @param ActivityDataPoint[] $dataPoints */
    public function __construct(private Pace $treshold, private HeartRate $maxHeartRate, private array $dataPoints)
    {
        Assert::that(!$treshold->isZero(), 'Please configure a treshold pace!');
        Assert::that($maxHeartRate->isValid(), 'Please configure a max. heart rate!');
    }

    /** @return PaceZoneEntry[] */
    public function getPaceZoneEntries(): array
    {
        /** @var PaceZoneEntry[] $result */
        $result = array_reduce(
            iterator_to_array($this->yieldPaceZoneEntries()),
            self::reduce(...),
            []
        );

        return $result;
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
        foreach ($this->dataPoints as $dataPoint) {
            if ($dataPoint->hasVelocity()) {
                $zone = $dataPoint->getVelocityAverage()->asPace()->getZone($this->treshold);
                yield new PaceZoneEntry($zone, $dataPoint->getTimestamp());
            }
        }
    }

    /** @return \Generator<HeartRateZoneEntry> */
    private function yieldHeartRateEntries(): \Generator
    {
        foreach ($this->dataPoints as $dataPoint) {
            $zone = $dataPoint->getHeartRate()->getZone($this->maxHeartRate);

            yield new HeartRateZoneEntry($zone, $dataPoint->getTimestamp());
        }
    }

    /**
     * @param array<PaceZoneEntry|HeartRateZoneEntry> $carry
     *
     * @return array<PaceZoneEntry|HeartRateZoneEntry>
     */
    private static function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry $current): array
    {
        if (empty($carry)) {
            return [$current];
        }

        $previous = array_last($carry);

        return $previous->zone !== $current->zone ? [...$carry, $current] : $carry;
    }
}
