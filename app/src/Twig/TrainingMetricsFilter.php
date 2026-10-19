<?php

declare(strict_types=1);

namespace App\Twig;

use App\Momentum\TrainingMetrics;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TrainingMetricsFilter extends AbstractExtension
{
    public const MAX_HARD = 40.0; /* percent */
    public const MAX_EASY = 45.0; /* percent */

    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('get_metrics_description', $this->getDescription(...)),
            new TwigFilter('get_metrics_description_background_color', self::getBackgroundColor(...)),
        ];
    }

    public function getDescription(TrainingMetrics $metrics): string
    {
        if ($metrics->isTooHard(self::MAX_HARD)) {
            return '🔴 '.$this->translator->trans('training_metrics_suggestion.take_it_easy');
        }

        return $metrics->isBalanced(self::MAX_EASY, self::MAX_HARD)
            ? '🟢 '.$this->translator->trans('training_metrics_suggestion.keep_it_up')
            : '🟠 '.$this->translator->trans('training_metrics_suggestion.set_more_impulses');
    }

    private static function getBackgroundColor(TrainingMetrics $metrics): string
    {
        return $metrics->isBalanced(self::MAX_EASY, self::MAX_HARD)
            ? 'bg-green-600'
            : 'bg-amber-500';
    }
}
