<?php

declare(strict_types=1);

namespace App\Momentum;

use App\Momentum\Assert\Assert;

final readonly class ZoneEntries
{
    private \DateInterval $gapTreshold;

    /** @param ActivityDataPoint[] $dataPoints */
    private function __construct(private Pace $treshold, private HeartRate $maxHeartRate, private array $dataPoints)
    {
        Assert::that(!$treshold->isZero(), 'Please configure a treshold pace!');
        Assert::that($maxHeartRate->isValid(), 'Please configure a max. heart rate!');

        $this->gapTreshold = \DateInterval::createFromDateString('5 seconds');
    }

    /** @param ActivityDataPoint[] $dataPoints */
    public static function create(AthleteInterface $athlete, array $dataPoints): self
    {
        return new self($athlete->getRunTreshold(), $athlete->getMaxHeartRate(), $dataPoints);
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
        for ($i = 0; $i < count($this->dataPoints); ++$i) {
            $current = $this->dataPoints[$i];
            $entry = PaceZoneEntry::fromDataPoint($current, $this->treshold);

            if (0 == $i) {
                yield $entry;
            } else {
                // Soley append when gap treshold is exceeded OR pace zones vary:
                $previous = $this->dataPoints[$i - 1];

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
        for ($i = 0; $i < count($this->dataPoints); ++$i) {
            $current = $this->dataPoints[$i];
            $entry = HeartRateZoneEntry::fromDataPoint($current, $this->maxHeartRate);

            if (0 == $i) {
                yield $entry;
            } else {
                // Soley append when gap treshold is exceeded OR heart rate zones vary:
                $previous = $this->dataPoints[$i - 1];
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
