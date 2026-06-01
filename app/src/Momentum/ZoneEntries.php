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
        // $entries = iterator_to_array($this->yieldPaceZoneEntries());

        // var_dump(['entries' => array_map(
        //     static fn (PaceZoneEntry $e) => (string) $e,
        //     $entries
        // )]);

        // /** @var PaceZoneEntry[] $result */
        // $result = array_reduce(
        //     $entries,
        //     self::reduce(...),
        //     []
        // );

        $result = iterator_to_array($this->yieldPaceZoneEntries());

        var_dump(['result' => array_map(
            static fn (PaceZoneEntry $e) => (string) $e,
            $result
        )]);

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
        // foreach ($this->dataPoints as $dataPoint) {
        //     // var_dump(['data_point' => (string) $dataPoint]);

        // if ($dataPoint->hasVelocity()) {
        //        yield PaceZoneEntry::fromDataPoint($dataPoint, $this->treshold);
        // }
        // }

        // var_dump([
        //     'data_points' => array_map(
        //         static fn (ActivityDataPoint $p) => (string) $p,
        //         $this->dataPoints
        //     )
        // ]);

        $gapTreshold = \DateInterval::createFromDateString('5 seconds');

        for ($i = 0; $i < count($this->dataPoints); ++$i) {
            $current = $this->dataPoints[$i];
            $entry = PaceZoneEntry::fromDataPoint($current, $this->treshold);

            if (0 == $i) {
                // var_dump([sprintf('yield %d', $i) => $entry]);
                yield $entry;
            } else {
                // Soley append when gap treshold exceeds OR pace zones vary:
                $previous = $this->dataPoints[$i - 1];

                if (
                    $previous->getTimestamp()->add($gapTreshold) < $current->getTimestamp()
                    || $previous->getPaceZone($this->treshold) !== $current->getPaceZone($this->treshold)
                ) {
                    // var_dump([sprintf('yield %d', $i) => $entry]);
                    yield $entry;
                }
            }
        }
    }

    /** @return \Generator<HeartRateZoneEntry> */
    private function yieldHeartRateEntries(): \Generator
    {
        // foreach ($this->dataPoints as $dataPoint) {
        //     $zone = $dataPoint->getHeartRate()->getZone($this->maxHeartRate);

        //     yield new HeartRateZoneEntry($zone, $dataPoint->getTimestamp());
        // }

        $gapTreshold = \DateInterval::createFromDateString('5 seconds');

        for ($i = 0; $i < count($this->dataPoints); ++$i) {
            $current = $this->dataPoints[$i];
            $entry = HeartRateZoneEntry::fromDataPoint($current, $this->maxHeartRate);

            if (0 == $i) {
                // var_dump([sprintf('yield %d', $i) => $entry]);
                yield $entry;
            } else {
                // Soley append when gap treshold exceeds OR pace zones vary:
                $previous = $this->dataPoints[$i - 1];
                if (
                    $previous->getTimestamp()->add($gapTreshold) < $current->getTimestamp()
                    || $previous->getHeartRateZone($this->maxHeartRate) !== $current->getHeartRateZone($this->maxHeartRate)
                ) {
                    // var_dump([sprintf('yield %d', $i) => $entry]);
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
    private static function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry $current): array
    {
        // var_dump(['current' => (string) $current]);

        if (empty($carry)) {
            return [$current];
        }

        $previous = array_last($carry);

        $gapTreshold = \DateInterval::createFromDateString('5 seconds');

        // Append when gap treshold exceeds:
        if ($current->entered > $previous->entered->add($gapTreshold)) {
            return [...$carry, $current];
        }

        // Append when different zone:
        if ($previous->zone !== $current->zone) {
            return [...$carry, $current];
        }

        // Don't append:
        return $carry;
    }
}
