<?php

declare(strict_types=1);

namespace App\Twig;

use App\Momentum\TrainingMetrics;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TrainingMetricsFilter extends AbstractExtension
{
    public const MAX_HARD = 40.0; /* percent */
    public const MAX_EASY = 45.0; /* percent */

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('get_metrics_description', self::getDescription(...)),
            new TwigFilter('get_metrics_description_background_color', self::getBackgroundColor(...)),
        ];
    }

    private static function getDescription(TrainingMetrics $metrics): string
    {
        if ($metrics->isTooHard(self::MAX_HARD)) {
            return '🔴 Take it easy.';
        }

        return $metrics->isBalanced(self::MAX_EASY, self::MAX_HARD)
            ? '🟢 Good balance of easy and hard work.'
            : '🟠 Set some more impulses.';
    }

    private static function getBackgroundColor(TrainingMetrics $metrics): string
    {
        return $metrics->isBalanced(self::MAX_EASY, self::MAX_HARD)
            ? 'bg-green-600'
            : 'bg-amber-500';
    }
}
